<?php
	if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
	if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
	if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
	if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
	
	DEFINE('INC_FROM_CRON_SCRIPT', true);
	
	require('../config.php');
	
	dol_include_once('/core/login/functions_dolibarr.php');
	dol_include_once('/contact/class/contact.class.php');
	dol_include_once('/product/class/product.class.php');
	dol_include_once('/comm/propal/class/propal.class.php');

// TODO sync event, propal, order, invoice in order to allow view and edit

	$get = GETPOST('get');
	$put = GETPOST('put');

	$login = GETPOST('login', 'alpha');
	$passwd = GETPOST('passwd', 'alpha');
	$entity = GETPOST('entity', 'int');
	
	// Pour des raisons de sécurité il est nécessaire de vérifier la connexion à chaque demande. A modifier côté JS pour envoyer constament le login/mdp
	$is_user = _check($login, $passwd, $entity);
	if ($is_user == 'ko')
	{
		__out($langs->trans('access_denied'));
		exit;
	} 
	else {
		$user->fetch('', $login, '', 1, $entity);
	}

	switch ($get) {
		case 'check':
			__out($is_user);
			
			break;
		
		case 'product':
			$id = GETPOST('id','int');
			
			if($id) __out(_getItem($get, $id));
			else __out(_getListItem($get, ' WHERE tosell = 1'));
			
			break;
			
		case 'thirdparty':
		case 'proposal':
			$id = GETPOST('id','int');
			
			if($id) __out(_getItem($get, $id));
			else __out(_getListItem($get));
			
			break;
                        
                /*case 'contact':
			$id = GETPOST('id','int');
			
			if($id) __out(_getItem($get, $id));
			else __out(_getListItem($get));
			
			break;
		*/	
	}	

	switch ($put) {
		case 'product':
			$TProduct = GETPOST('TItem');
			$TProduct = json_decode($TProduct);
			
			$response = _updateDolibarr($user, $TProduct, 'Product');
			__out($response);
			
			break;
			
		case 'thirdparty':
			$TSociete = GETPOST('TItem');
			$TSociete = json_decode($TSociete);
			
			$response = _updateDolibarr($user, $TSociete, 'Societe');
			__out($response);
			
			break;
			
		case 'proposal':
			$TProposal = GETPOST('TItem');
			$TProposal = json_decode($TProposal);
			
			$response = _updateDolibarr($user, $TProposal, 'Proposal');
			__out($response);
			break;
                    
                case 'contact':
			$TContact = GETPOST('TItem');
			$TContact = json_decode($TContact);
			
			$response = _updateDolibarr($user, $TContact, 'Contact');
			__out($response);
			break;
	}
	
function _check($login, $passwd, $entity)
{
	$res = check_user_password_dolibarr($login, $passwd, $entity);
	
	if (!empty($res)) return 'ok';
	else return 'ko';
}

function _getItem($type, $id)
{
	global $db;
	if ($type == 'product') $className = 'Product';
	elseif ($type == 'thirdparty') $className = 'Societe';
	elseif ($type == 'proposal') $className = 'Propal';
        elseif ($type == 'contact') $className = 'Contact';
	else exit($type.' => _getItem non géré');
	
	$o=new $className($db);
	$o->fetch($id);
        /*var_dump($o);
        exit("o");
	*/
        if($type=='thirdparty') {
            
            $TContact = $o->contact_array_objects();
            $o->TContact = $TContact;
        }
        
        
	return $o;
}

function _getListItem($type, $filter='')
{
	global $conf;
	
	if ($type == 'product') $table = 'product';
	elseif ($type == 'thirdparty') $table = 'societe';
	elseif ($type == 'proposal') $table = 'propal';
        elseif ($type == 'contact') $table = 'socpeople';
	else exit('*'.$type.'* => _getListItem non géré');
	
	$PDOdb = new TPDOdb;
	$limit = empty($conf->global->STANDALONE_SYNC_LIMIT_LAST_ELEMENT) ? 100 : $conf->global->STANDALONE_SYNC_LIMIT_LAST_ELEMENT;
	
	$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.$table;
	if (!empty($filter)) $sql .= $filter;
	$sql.= ' ORDER BY tms DESC LIMIT '.$limit;
	
	$Tab = $PDOdb->ExecuteAsArray($sql);
	
	$TResult = array();
	foreach ($Tab as $row) 
	{
		$TResult[$row->rowid] = _getItem($type, $row->rowid);
	}
	
	return $TResult;
}

function _updateDolibarr(&$user, &$TObject, $classname)
{
	global $langs,$db,$conf;
	$TError = array();
	
	foreach ($TObject as $objStd)
	{
		$objDolibarr = new $classname($db);
		// TODO Pour un gain de performance ça serait intéressant de ne pas faire de fetch, mais actuellement nécessaire pour éviter un retour d'erreur non géré pour le moment
		
		
		$resFetch = $objDolibarr->fetch($objStd->id_dolibarr);
                // $objDolibarr->array_options = array(); // TODO pas encore géré
		foreach ($objStd as $attr => $value)
		{
			if (is_object($objDolibarr->{$attr})) continue;
			elseif (is_array($objDolibarr->{$attr})) continue;
			else $objDolibarr->{$attr} = $value;
		}
		if(empty($objStd->id_dolibarr)){
			$objDolibarr->id = null;
		}
		switch ($classname) {
			case 'Product':
			case 'Societe':
                            if(!empty($objStd->TContact)){
								foreach($objStd->TContact as &$myContact){
									if(empty($myContact->id)){
										
										$myContact->socid = $objStd->id_dolibarr;
										
									}
									
										
									
									
								}
								_updateDolibarr($user, $objStd->TContact, 'Contact');
							}
								$objDolibarr->client = $objDolibarr->Type;
								($objDolibarr->fournisseur=="Yes") ? $objDolibarr->fournisseur=1 :$objDolibarr->fournisseur=0  ; 
								
								
                                $res = $resFetch > 0 ? $objDolibarr->update($objStd->id_dolibarr, $user) : $objDolibarr->create($user);
				break;
			case 'Propal':
				
				$objDolibarr->socid = $objStd->socid;
				$objDolibarr->remise = 0;
				$objDolibarr->datep = time();
				$objDolibarr->author = $user->id;
				$objDolibarr->duree_validite = $conf->global->PROPALE_VALIDITY_DURATION;
				
				$sql = "SELECT rowid,libelle as label";
				$sql.= " FROM ".MAIN_DB_PREFIX.'c_payment_term';
				$sql.= " WHERE active > 0 AND libelle LIKE '%".$objStd->cond_reglement."%'";
				$sql.= " ORDER BY sortorder";
				
				$res = $db->query($sql);
				if($res){
					$obj = $db->fetch_object($res);
					if($obj){
						$objDolibarr->cond_reglement_id=$obj->rowid;

					
					} else {
						$objDolibarr->cond_reglement_id=1;
					}
					
				}else {
					$objDolibarr->cond_reglement_id=1;
				}
				
				$sql = "SELECT rowid,libelle as label";
				$sql.= " FROM ".MAIN_DB_PREFIX.'c_paiement';
				$sql.= " WHERE active > 0 AND libelle LIKE '%".$objStd->cond_reglement."%'";
				$sql.= " ORDER BY sortorder";
				
				$res = $db->query($sql);
				if($res){
					$obj = $db->fetch_object($res);
					if($obj){
						$objDolibarr->mode_reglement_id=$obj->rowid;

					
					} else {
						$objDolibarr->mode_reglement_id=1;
					}
					
				}else {
					$objDolibarr->mode_reglement_id=1;
				}
				$objDolibarr->id = $objDolibarr->create($user);
				if(!empty($objStd->lines)){
					foreach($objDolibarr->lines as $line){
						$objDolibarr->addline($line->ref, $totalHT, 1, 0, 0, 0, $prodCommissionnement->id);
					}
					
				}
				$commFourn->updateline($commFourn->line->id, $desc, $totalHT, 1, 0, 0);
				
								
				// cas spéciale, pas de function update et il va falloir sauvegarder les lignes
                                   break;
            case 'Contact':
				
                $res = $resFetch > 0 ? $objDolibarr->update($objStd->id, $user) : $objDolibarr->create($user);
				
				
                            
                break;
                            
            default:
				
			break;
		}
		
		$data = json_encode(array('classname == '.$classname));
		$str = '<script type="text/javascript">
					var data = '.$data.';
					window.parent.postMessage(data, "*");
				</script>';
		//return $str;
		
		/* TODO retour d'erreur non géré encore
		if ($res < 0)
		{
			$TError[] = $langs->trans('');
		}
		 * 
		 */
	}
	
	return $TError;
}