<?php
function getTemplates(&$cabinets, &$documents, &$templates) {
	$documents = array (
		'Correspondence' => array (
			'indices' => array (
				'description',
			),
		),

		'Email' => array (
			'indices' => array (
				'to',
				'from',
				'subject',
				'date',
				'cc',
				'bcc',
			),
		),

		'Estimate' => array (
			'indices' => array (
				'description',
			),
		),

		'Medical Bill' => array (
			'indices' => array (
				'description',
			),
		),

		'Photos' => array (
			'indices' => array (
				'description',
			),
		),

		'Police Report' => array (
			'indices' => array (
				'description',
			),
		),

		'Signed Forms' => array (
			'indices' => array (
				'description',
			),
		),

		'Signed Application' => array (
			'indices' => array (
				'description',
			),
		),

		'Endorsement' => array (
			'indices' => array (
				'description',
			),
		),

		'Cancellation Reinstatement' => array (
			'indices' => array (
				'description',
			),
		),

		'Loss Run' => array (
			'indices' => array (
				'description',
			),
		),

		'Financials' => array (
			'indices' => array (
				'description',
			),
		),

		'Appraisal' => array (
			'indices' => array (
				'description',
			),
		),
	);

	$cabinets = array (
		'Student Records' => array (
			'indices' => array (
				'student id',
				'student first name',
				'student last name',
				'date of birth',
			),
		),

		'Human Resources' => array (
			'indices' => array (
				'employee number',
				'first name',
				'last name',
				'position',
				'date of hire',
			),
		),

		'Accounts Payable' => array (
			'indices' => array (
				'voucher number',
				'invoice number',
				'vendor',
				'amount',
			),
		),

		'Accounts Receivable' => array (
			'indices' => array (
				'client',
				'po',
				'amount',
			),
		),

		'Fundraising' => array (
			'indices' => array (
				'donor transaction',
				'donor',
				'event',
				'amount',
				'date',
			),
		),

		'Board Minutes and Agendas' => array (
			'indices' => array (
				'date',
				'meeting description',
				'governing body',
			),
		),

		'Job Description' => array (
			'indices' => array (
				'classification title',
				'position status',
				'class code',
				'date',
				'item_number',
			),
		),

		'Facilities Planning' => array (
			'indices' => array (
				'building',
				'floor',
				'document type',
			),
		),

		'Legal Arbitration' => array (
			'indices' => array (
				'case number',
				'plaintiff',
				'defendent',
				'date case opened',
				'date case closed',
			),
		),

		'Vendor' => array (
			'indices' => array (
				'vendor name',
				'contact name',
			),
		),

		'Patient Records' => array (
			'indices' => array (
				'patient number',
				'patient first name',
				'patient last name',
			),
		),

		'Policy and Procedures' => array (
			'indices' => array (
				'policy number',
				'policy name',
				'group effected',
			),
		),

		'Contracts' => array (
			'indices' => array (
				'contract number',
				'name',
				'client',
			),
		),

		'Explanation of Benefits' => array (
			'indices' => array (
				'financial class',
				'transaction date',
			),
		),

		'Clients' => array (
			'indices' => array (
				'client number',
				'client name',
				'address',
			),
			'document_view' => true,
			'documents' => array (
				'Photos',
				'Loss Run',
				'Financials',
				'Appraisal',
				'Correspondence',
				'Email',
			),
		),

		'Policies' => array (
			'indices' => array (
				'policy number',
				'client name',
				'type of coverage',
				'term end date',
			),
			'document_view' => true,
			'documents' => array (
				'Signed Application',
				'Endorsement',
				'Cancellation Reinstatement',
				'Signed Forms',
				'Correspondence',
				'Email',
			),
		),

		'Activities' => array (
			'indices' => array (
				'client name',
				'description',
				'entered date',
			),
		),

		'Claims' => array (
			'indices' => array (
				'claim number',
				'client name',
				'policy number',
				'type of loss',
				'date of claim',
			),
			'document_view' => true,
			'documents' => array (
				'Photos',
				'Signed Forms',
				'Police Report',
				'Medical Bill',
				'Estimate',
				'Correspondence',
				'Email',
			),
		),

		'Licenses' => array (
			'indices' => array (
				'name',
			),
		),

		'Beneficiary Forms' => array (
			'indices' => array (
				'case number',
				'client',
				'id number',
				'date',
			),
		),

		'TPA Clients' => array (
			'indices' => array (
				'client name',
				'doc type',
				'back up',
				'allocation reports',
				'census',
				'compliance test',
				'government forms',
			),
		),

		'Custodian Reports' => array (
			'indices' => array (
				'index1',
			),
		),

		'Enrollment Kits' => array (
			'indices' => array (
				'case number',
				'client name',
				'required by date',
				'quantity',
				'due date',
				'meeting date',
				'sample kit',
				'sales person',
			),
		),

		'Enrollments' => array (
			'indices' => array (
				'case number',
				'client name',
				'date',
				'number',
			),
		),

		'Forms' => array (
			'indices' => array (
				'iso number',
				'form number',
				'form name',
			),
		),

		'IRS1099' => array (
			'indices' => array (
				'client number',
				'client name',
				'year',
			),
		),

		'Legacy Claims' => array (
			'indices' => array (
				'index1',
			),
		),

		'Life Reinsurance Enrollment Kits' => array (
			'indices' => array (
				'index1',
			),
		),

		'Loans' => array (
			'indices' => array (
				'case number',
				'client',
				'account number',
				'participant name',
				'ssn',
				'date received',
			),
		),

		'Payroll' => array (
			'indices' => array (
				'case number',
				'client',
				'payroll date',
				'account number',
			),
		),

		'Plan Year End' => array (
			'indices' => array (
				'case number',
				'client',
				'plan year end',
				'status',
				'date received',
				'administrator',
			),
		),

		'Projects' => array (
			'indices' => array (
				'project name',
				'project state date',
				'closed date',
			),
		),

		'Rollovers' => array (
			'indices' => array (
				'client number',
				'client name',
				'account number',
				'date',
				'amount',
				'participant name',
				'ssn',
				'status',
			),
		),

		'Statements' => array (
			'indices' => array (
				'case number',
				'client name',
				'ssn',
				'quarter end date',
			),
		),

		'Repair Orders' => array (
			'indices' => array (
				'ro number',
				'customer number',
				'customer name',
				'vin',
				'make and model',
				'year',
				'ro opened',
				'ro closed',
				'license',
			),
		),

		'Engineering Designs' => array (
			'indices' => array (
				'index1',
			),
		),

		'Change Order Requests' => array (
			'indices' => array (
				'index1',
			),
		),

		'Expenses' => array (
			'indices' => array (
				'expense number',
				'employee name',
				'cost cent',
				'amount',
				'description',
			),
		),

		'Training' => array (
			'indices' => array (
				'course',
				'description',
				'document type',
			),
		),

		'Logs' => array (
			'indices' => array (
				'date',
				'material',
			),
		),

		'Property' => array (
			'indices' => array (
				'property number',
				'property name',
				'address',
				'unit',
			),
		),

		'City Clerk' => array (
			'indices' => array (
				'index1',
			),
		),

		'Planning Board' => array (
			'indices' => array (
				'parcel id',
				'address',
				'owner',
				'town department',
			),
		),

		'Cases' => array (
			'indices' => array (
				'index1',
			),
		),

		'Resolution' => array (
			'indices' => array (
				'resolution numver',
				'resolution title',
				'resolution description',
				'sponsor',
				'action date',
				'status',
			),
		),

		'Request for Bids and Responses' => array (
			'indices' => array (
				'fiscal year',
				'type',
				'number',
				'description',
				'department',
				'open date',
			),
		),
	);

	$templates = array (
		'Education' => array (
			'Vendor',
			'Legal Arbitration',
			'Facilities Planning',
			'Job Description',
			'Board Minutes and Agendas',
			'Fundraising',
			'Accounts Receivable',
			'Accounts Payable',
			'Human Resources',
			'Student Records',
		),

		'Medical' => array (
			'Patient Records',
			'Human Resources',
			'Accounts Payable',
			'Accounts Receivable',
			'Policy and Procedures',
			'Contracts',
			'Explanation of Benefits',
			'Vendor',
		),

		'Insurance' => array (
			'Clients',
			'Policies',
			'Activities',
			'Claims',
			'Human Resources',
			'Licenses',
			'Accounts Payable',
			'Accounts Receivable',
		),

		'TPA' => array (
			'Beneficiary Forms',
			'Clients',
			'Custodian Reports',
			'Enrollment Kits',
			'Enrollments',
			'Forms',
			'IRS1099',
			'Legacy Claims',
			'Life Reinsurance Enrollment Kits',
			'Loans',
			'Payroll',
			'Plan Year End',
			'Policies',
			'Projects',
			'Rollovers',
			'Statements',
		),

		'Automotive' => array (
			'Contracts',
			'Repair Orders',
			'Human Resources',
			'Accounts Payable',
			'Accounts Receivable',
			'Loans',
			'Legal Arbitration',
		),

		'TitleRealEstate' => array (
			'Human Resources',
			'Accounts Payable',
			'Accounts Receivable',
			'Loans',
			'Legal Arbitration',
			'Contracts',
		),

		'Manufacturing' => array (
			'Accounts Payable',
			'Accounts Receivable',
			'Engineering Designs',
			'Change Order Requests',
			'Contracts',
			'Human Resources',
			'Expenses',
			'Training',
			'Legal Arbitration',
			'Logs',
		),

		'PropertyManagement' => array (
			'Human Resources',
			'Accounts Payable',
			'Legal Arbitration',
			'Contracts',
			'Property',
		),

		'Construction' => array (
			'Human Resources',
			'Accounts Payable',
			'Accounts Receivable',
			'Projects',
		),

		'Government' => array (
			'City Clerk',
			'Planning Board',
			'Cases',
			'Accounts Payable',
			'Accounts Receivable',
			'Resolution',
			'Board Minutes and Agendas',
			'Request for Bids and Responses',
		),
	);
}
?>
