<?php
//this was created for rosen management to enter a new property and have 3 cabinets created with the proper index type definitions. this file is to be placed in the tools directory.
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../lib/odbc.php';
include_once '../lib/quota.php';
include_once '../lib/random.php';
include_once '../lib/settings.php';
include_once '../lib/sagWS.php';
include_once '../modules/modules.php';
include_once '../settings/settings.php';
include_once '../documents/documents.php';
include_once '../lib/xmlObj.php';
if($logged_in==1 && strcmp($user->username,"")!=0) {
	if( isSet($_POST['cab'] ) ) {
		$cab = $_POST['cab'];
	 	$docutron = getDbOBject('docutron');
	 	$department="client_files";
		$db_object = getDbOBject($department);
		$newRealCab=str_replace(" ", "_", $cab)."_AP";
		$newRealCab=str_replace("-", "_", $newRealCab);
		$newArbCab=$cab." AP";
		$indiceName=array();
		$indiceName[]="accounting_code"; //need index type def
		$indiceName[]="date";
		$indiceName[]="unit";
		$indiceName[]="multiple_codes";
		$indiceName[]="payment_1_date";
		$indiceName[]="payment_2_date";
		$indiceName[]="payment_3_date";
		$indiceName[]="payment_4_date";
		$res = createFullCabinet($db_object, $docutron, $department, $newRealCab, $newArbCab, $indiceName);
		$sArr = array('departmentid');
		$wArr = array('real_name' => $newRealCab);
		$cabID = getTableInfo($db_object,'departments',$sArr,$wArr,'queryOne');
		$k="dt,".$department.",".$cabID.",accounting_code";
		$value="2110 Loan Principal 1,,,2120 Loan Principle 2,,,2210 Prepaid assessment,,,2250 Move in/out deposit,,,2252 Remodeling deposit,,,2255 Association Tenant Deposits,,,2256 Elevator deposit,,,2265 Bike Deposit,,,2266 Pet deposit,,,2267 Garage Opener Deposit,,,2268 Hospitality Room deposit,,,2269 Key deposit,,,2275 Recreation Room Deposit,,,2410 Federal Withholding Payable,,,2420 State Withholding,,,2430 FICA Tax,,,2440 FUTA Tax Payable,,,2450 State Unemployment Tax,,,2630 Employee Credit Union,,,2640 Workers Union Dues,,,5020 Gas,,,5040 Electric,,,5060 Telephone,,,5065 Internet Provider Expense,,,5080 Water and Sewer,,,5125 Awning Cleaning,,,5130 exterminating,,,5140 Scavenger,,,5150 Recycling,,,5160 Janitorial,,,5165 Handyman,,,5170 cleaning services,,,5175 HVAC Maintenance Contract,,,5180 Elevator Contract Maintenance,,,5185 Elevator Monitoring Fees,,,5200 Snow removal,,,5220 Landscape,,,5221 Core Aeration,,,5222 Landsccape Improvements,,,5223 Grub Control,,,5224 Soil Grading,,,5225 Lawn Sprinkler Maintenance,,,5230 Wildlife Control,,,5232 Ice Control,,,5233 Pond Repair,,,5234 Lakes Ponds Fountains,,,5240 Window Washing,,,5245 Power Washing,,,5250 Driveway Gates,,,5260 Carpet Cleaning,,,5265 Towing,,,5270 Laundry Service,,,5274 Pool repairs,,,5275 Pool Maintenance,,,5277 Hallway Lights,,,5278 Maintenance Uniforms,,,5280 Security,,,5281 Fire Protection,,,5285 Cable TV,,,5290 Fire extinguishers,,,5315 Elevator repairs,,,5320 Electrical,,,5325 Exer. Equip/Hosp. Rm Rep-Replc,,,5330 Plumbing,,,5331 Rodding,,,5335 Ventilation,,,5340 Heating,,,5341 Water Heater Repair,,,5345 HVAC Repair,,,5350 Air Conditioning,,,5360 Painting and Decorating,,,5361 unit painting and decorating,,,5365 Exterior Paint,,,5370 Doors/Locks/Fences,,,5375 Appliances,,,5380 Garage and Parking Area,,,5382 Street Repairs,,,5384 Landscape Improvements and Upgrades,,,5385 Fountain,,,5386 Tree Trimming,,,5390 Roof,,,5395 Masonry and Tuckpointing,,,5397 Chimney,,,5400 Intercom and Mailboxes,,,5405 Laundry Equipment Repairs,,,5415 Sidewalks and Patios,,,5420 Porches Decks,,,5425 Awnings,,,5430 Fire Sprinklers,,,5432 Gravity Tank,,,5435 Directories,,,5440 Widows,,,5442 Lobby Expenses,,,5444 Balconies,,,5445 Gutters and Downspouts,,,5446 Graffiti Removal,,,5450 Carpet Tile,,,5452 Hallway Stairs,,,5456 Paving,,,5457 Environmental Remediation,,,5460 Floor or Stair Repair,,,5470 Exterior Lighting,,,5475 Insurance Repairs,,,5476 Unit Owner Damages,,,5480 Maintenance Or General Repairs,,,5485 Antenna,,,5520 Supplies,,,5540 Supplies Landscaping,,,5550 Supplies Light Bulbs,,,5555 Supplies Equipment,,,5556 Supplies Signs,,,5560 Supplies Bike Rack,,,5565 Supplies Uniforms,,,5570 Supplies Pool,,,5630 Management,,,5635 Bank Charges,,,5638 Annual Reports,,,5640 Miscellaneous Administrative,,,5641 Prior Year Expense,,,5642 Bonuses,,,5643 Lockouts,,,5644 Professional Association Dues,,,5645 Fines Government,,,5646 Entertainment,,,5647 Messenger Service,,,5648 Condo Association Expense,,,5648 Pagers/Phones/Radios,,,5650 Accounting and Audit,,,5651 Consulting Fees,,,5652 Party Fees,,,5653 Petty Cash,,,5654 Master Association Dues,,,5655 Leases,,,5656 Gifts,,,5660 Coupon Books,,,5662 Permits,,,5665 Office Equipment,,,5670 Inspection and Filing Fees,,,5672 Meeting Expenses,,,5675 Legal,,,5677 Advertising and Promotion,,,5678 Trust Expenses,,,5680 Evictions,,,5700 Association Transfer,,,5800 Package Liability and Fire,,,5805 Umbrella Coverage,,,5810 Workers Compensation,,,5815 Directors and Officers Liability,,,5816 Fidelity Insurance,,,5820 Boiler and Machinery Insurance,,,5825 Insurance Claim Expense,,,5850 Insurance Service Charges,,,5920 Capital Improvement,,,5921 Gutter/Downspouts,,,5922 Engineer/Architectural Studies,,,5923 Front Awning,,,5925 Siding,,,5926 Storage Lockers,,,5933 Roof,,,5934 Masonry and tuckpointing,,,5935 Painting/Wallpaper/Decorating,,,5936 Stairways,,,5937 Emergency Lights,,,5938 Doors/Fences/Gates,,,5939 Intercom or Mailboxes,,,5940 Porches/Decks/Balconies,,,5941 Elevator,,,5942 Carpeting,,,5943 Chimney,,,5944 Lobby Renovation,,,5945 Heating,,,5946 Plumbing or Hot Water Heater,,,5947 Sidewalks and Patios,,,5948 Windows,,,5949 Sprinkler or Fire System,,,5950 Electrical,,,5951 Landscaping,,,5952 Garage Floor,,,5953 Recreation Room Renovation,,,5954 Laundry Room renovation,,,5955 Capital Project Prior Year,,,5956 Asbestos Removal,,,5957 Exterior Painting,,,5959 Lintels,,,5960 Washing Machines or Dryer,,,5961 Security,,,5962 Air Conditioning,,,5963 Fountain,,,5964 Pools,,,5965 Furniture,,,5966 HVAC,,,5970 Paving,,,5971 Sprinkler or Lawns,,,5972 Ventilation,,,5974 Fire Alarm System,,,5975 Decorating,,,5980 Bike Racks,,,5985 Basement Insulation,,,5986 Basement Floor,,,5990 Reserve Study,,,6100 Federal Income Tax,,,6150 State Income Tax,,,6200 Real Estate Taxes,,,6205 Property Tax Escrow,,,6250 Corporation State Fees,,,6255 Franchise Tax,,,7810 Manager Salaries,,,7820 Supervisor Salaries,,,7830 Clerical Salaries,,,7840 Janitor Salaries,,,7841 Janitor House Rent,,,7842 Miscellaneous Deduction,,,7845 Payroll Bonuses,,,7850 Payroll Taxes,,,7855 FICA Tax,,,7858 SUTA Tax,,,7859 FUTA Tax,,,7860 Health and Welfare,,,8500 Debt Expense,,,8510 1st Mortgage Interest,,,8520 2nd Mortgage Principal,,,8530 2nd Mortgage Interest,,,8550 3rd Mortgage Interest,,,9620 Operating to Reserve 1,,,9625 Operating to Reserve 2,,,9630 Operating to Reserve 3,,,9635 Operating to Reserve 4,,,9640 Operating to Reserve 5,,,9645 Operating to Reserve 6,,,9650 Operating to Reserve 7,,,9655 Operating to Reserve 8,,,9660 Operating to Reserve 9,,,9665 Operating to Reserve 10,,,9670 Operating to Reserve 11,,,9720 Reserve 1 to Operating,,,9725 Reserve 2 to Operating,,,9730 Reserve 3 to Operating,,,9735 Reserve 4 to Operating,,,9745 Reserve 6 to Operating,,,9750 Reserve 5 to Operating,,,9750 Reserve 7 to Operating,,,9755 Reserve 8 to Operating,,,9760 Reserve 9 to Operating,,,9765 Reserve 10 to Operating,,,9775 Reserve to Reserve Expense,,,Multiple account code";
		$query="insert into settings (k,value,department) value('".$k."','".$value."','".$department."')";
		$results=$docutron->query($query);
	echo $newArbCab.' cabinet created<br>';	
		$newRealCab=str_replace(" ", "_", $cab)."_Unit_Docs";
		$newRealCab=str_replace("-", "_", $newRealCab);
		$newArbCab=$cab." Unit Docs";
		$indiceName=array();
		$indiceName[]="unit";
		$indiceName[]="owners";
		$res = createFullCabinet($db_object, $docutron, $department, $newRealCab, $newArbCab, $indiceName);
	echo $newArbCab.' cabinet created<br>';	
	
		$newRealCab=str_replace(" ", "_", $cab)."_Property_file";
		$newRealCab=str_replace("-", "_", $newRealCab);
		$newArbCab=$cab." Property file";
		$indiceName=array();
		$indiceName[]="document_type"; //need index type def
		$indiceName[]="description";
		$indiceName[]="date";
		$res = createFullCabinet($db_object, $docutron, $department, $newRealCab, $newArbCab, $indiceName);
		$sArr = array('departmentid');
		$wArr = array('real_name' => $newRealCab);
		$cabID = getTableInfo($db_object,'departments',$sArr,$wArr,'queryOne');
		$k="dt,".$department.",".$cabID.",document_type";
		$value="Air conditioning,,,Board Correspondence,,,Boiler/Hot Water Heater,,,Certificate of Compliance/License,,,City/Village Docs - Building codes/ compliance issues,,,City/Village Docs - Fire Department,,,City/Village Docs - Violation Notices,,,Coupon Book,,,Elevator,,,Equipment Owned by Association,,,Financial document - Bank Signature card,,,Financial document - budget,,,Financial document - Certificate of deposit,,,Financial document - Lease for Association Property,,,Financial document - Loan doc,,,Financial document - Misc Exp. Reimbursement,,,Financial document - Real Estate Taxes,,,Financial document - special assessment,,,Financial document - tax return/audit,,,Garage/ Parking,,,Gates and Fences,,,General Notices,,,Governing document - annual report to state,,,Governing document - articles of incorporation,,,Governing document - Decs and By laws,,,Governing document - developer document,,,Governing document - Reserve Study,,,Governing document - Rules and Regulations,,,Governing document - survey,,,Inspection Report,,,Insurance Application,,,Insurance Claim,,,Insurance Policy,,,Intercom,,,Janitor - 1099, W-2 etc.,,,Janitor - Employment doc's,,,Janitor - Independent contractor agreement,,,Janitor - job description,,,Janitor - Resume,,,Management Report,,,Meeting Agenda,,,Meeting Ballots and Election Results,,,Meeting Minutes,,,Meeting Notices,,,Meeting Sign in Sheet/Proxy,,,Newsletter,,,Owners Directory,,,Parking Assignment,,,Proposal,,,Roof,,,Scavenger Rebate,,,Signed Contracts/ Warranties/ Cert.of Ins.,,,Storage Locker Assignment,,,Swimming Pool,,,Take over file";
		$query='insert into settings (k,value,department) value("'.$k.'","'.$value.'","'.$department.'")';
		$results=$docutron->query($query);
	echo $newArbCab.' cabinet created<br>';	
	} 
} else {
	logUserOut();
}
?>
<script type="text/javascript">
	function allowKeys(evt) {
        evt = (evt) ? evt : event;
        var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
        var pool = "1234567890 ";
        pool += "abcdefghijklmnopqrstuvwxyz";
        pool += "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        var character = String.fromCharCode(charCode);

        if( (pool.indexOf(character) != -1)
                || (charCode == 8) || (charCode == 9) || (charCode == 37) || (charCode == 39) )
            return true;
        else
            return false;
    }
</script>
	<form name="myform" action="RosenCreateCabinets.php" method="POST">
		<p>Enter Cabinet Prefix: <input onkeypress="return allowKeys(event)" type="text" name='cab'><p>
		<input type="submit" >
	</form>