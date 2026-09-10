import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-authorization',
  templateUrl: './authorization.component.html',
  styleUrls: ['./authorization.component.css']
})
export class AuthorizationComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getUsers();
    this.getPorts();
    this.getPlantdetail();
   
  }

  // Section 1: Authorization Type
  authorizationType: string = '';

  // Section 2: Applicant / Holder Details
  companyName: string = ''; // Auto from profile
  authorizationHolderName: string = '';
  designation: string = '';
  contactNumber: string = '';
  emailId: string = '';
  iecCode: string = '';
  gstTaxId: string = '';
  companyAddress: string = '';

  // Section 3: Authorization Document Details
  authorizationLicenseNumber: string = '';
  issuingAuthority: string = '';
  issueDate: string = '';
  validFromDate: string = '';
  validTillDate: string = '';
  status: string = 'Pending';

  // Section 4: Shipment / Product Scope
  productCategory: string = '';
  productName: string = '';
  hsCode: string = '';
  itcCode: string = '';
  drugLicenseNo: string = '';
  controlledSubstanceCheck: string = 'No';
  countryOfOrigin: string = '';
  countryOfDestination: string = '';
  portOfEntryExit: string = '';
  authorizedQuantity: number = 0;
  unit: string = '';
  usedQuantity: number = 0;
  remainingQuantity: number = 0;

  // Section 5: Compliance & Restrictions
  isProductRestricted: string = 'No';
  specialConditions: string = '';
  storageRequirement: string = '';
  hazardousMaterialDeclaration: string = 'No';
  scheduleDrugCategory: string = '';

  // Section 6: File Uploads
  authorizationCopyFile: File | null = null;
  annexuresFile: File | null = null;
  digitalSignatureFile: File | null = null;

  // Section 7: Audit Trail (Auto-filled)
  createdBy: string = '';
  createdDateTime: string = '';
  lastModifiedBy: string = '';
  modifiedDateTime: string = '';
  ipLogged: string = '';

  // Section 8: Renewal & Alerts
  autoRenewalReminderBefore: string = '30';
  expiryAlertToggle: string = 'Yes';
  notifyTo: string[] = [];

  // Section 9: Approval Workflow
  requiresInternalApproval: string = 'Yes';
  approver1: string = '';
  approver2: string = '';
  approver3: string = '';
  approvalStatus: string = 'Pending';
  approvalRemarks: string = '';

  // Dropdown data
  users: any[] = [];
  ports: any[] = [];
  productCategories: any[] = [];

  // Selected users for notification
  selectedNotifyUsers: string[] = [];
  notifyUser: string = '';
  plantdetail;
  // Get uplantdetailsers for dropdowns
  getPlantdetail() {
    this.service.get('common.php?type=getPlantdetail').subscribe((response: any) => {
      this.plantdetail = response[0] || [];
      this.authorizationHolderName = this.plantdetail.authorizationHolderName;
      this.designation = this.plantdetail.designation;
      this.contactNumber = this.plantdetail.contactNumber;
      this.emailId = this.plantdetail.emailId;
      this.companyAddress = this.plantdetail.plant_full_address;
      this.companyName = this.plantdetail.plant_full_name;
      console.log(this.plantdetail);
      console.log(this.authorizationHolderName);
      console.log(this.designation);
      console.log(this.contactNumber);
      console.log(this.emailId);
    }, error => {
      console.error('Error fetching users:', error);
      this.plantdetail = [];
    });
  }
  getUsers() {
    this.service.get('common.php?type=getUsers').subscribe((response: any) => {
      this.users = response || [];
    }, error => {
      console.error('Error fetching users:', error);
      this.users = [];
    });
  }

  // Get product categories from API
  getMaterialByType(value) {
    this.service.get('common.php?type=getMaterialByType&material_type='+value).subscribe((response: any) => {
      this.productCategories = response || [];
    }, error => {
      console.error('Error fetching product categories:', error);
      this.productCategories = [];
    });
  }

  // Get ports
  getPorts() {
    // You can add API call here if ports are stored in database
    this.ports = [
      'Mumbai Port',
      'Chennai Port',
      'Kolkata Port',
      'Kandla Port',
      'Cochin Port',
      'JNPT (Nhava Sheva)',
      'Tuticorin Port',
      'Visakhapatnam Port',
      'Paradip Port',
      'Mormugao Port'
    ];
  }

  // File upload handlers
  onAuthorizationCopyChange(event: any) {
    if (event.target.files.length > 0) {
      this.authorizationCopyFile = event.target.files[0];
    }
  }

  onAnnexuresChange(event: any) {
    if (event.target.files.length > 0) {
      this.annexuresFile = event.target.files[0];
    }
  }

  onDigitalSignatureChange(event: any) {
    if (event.target.files.length > 0) {
      this.digitalSignatureFile = event.target.files[0];
    }
  }

  // Calculate remaining quantity
  calculateRemainingQuantity() {
    this.remainingQuantity = this.authorizedQuantity - this.usedQuantity;
  }

  // Add user to notification list
  addNotifyUser(userId: string) {
    if (userId && !this.selectedNotifyUsers.includes(userId)) {
      this.selectedNotifyUsers.push(userId);
    }
  }

  // Remove user from notification list
  removeNotifyUser(index: number) {
    this.selectedNotifyUsers.splice(index, 1);
  }

  // Get user name by ID
  getUserName(userId: string): string {
    if (!userId || !this.users || this.users.length === 0) {
      return userId || '';
    }
    const user = this.users.find(u => (u.emp_id || u.id) === userId);
    return user ? (user.emp_name || user.name || userId) : userId;
  }

  // Save form
  saveForm(form: any) {
    if (!form.valid) {
      alertify.error('All required fields must be filled');
      return;
    }

    // Calculate remaining quantity
    this.calculateRemainingQuantity();

    // Create FormData for file uploads
    const uploadData = new FormData();
    const formData = {
      // Section 1
      authorization_type: this.authorizationType,
      
      // Section 2
      company_name: this.companyName,
      authorization_holder_name: this.authorizationHolderName,
      designation: this.designation,
      contact_number: this.contactNumber,
      email_id: this.emailId,
      iec_code: this.iecCode,
      gst_tax_id: this.gstTaxId,
      company_address: this.companyAddress,
      
      // Section 3
      authorization_license_number: this.authorizationLicenseNumber,
      issuing_authority: this.issuingAuthority,
      issue_date: this.issueDate,
      valid_from_date: this.validFromDate,
      valid_till_date: this.validTillDate,
      status: this.status,
      
      // Section 4
      product_category: this.productCategory,
      product_name: this.productName,
      hs_code: this.hsCode,
      itc_code: this.itcCode,
      drug_license_no: this.drugLicenseNo,
      controlled_substance_check: this.controlledSubstanceCheck,
      country_of_origin: this.countryOfOrigin,
      country_of_destination: this.countryOfDestination,
      port_of_entry_exit: this.portOfEntryExit,
      authorized_quantity: this.authorizedQuantity,
      unit: this.unit,
      used_quantity: this.usedQuantity,
      remaining_quantity: this.remainingQuantity,
      
      // Section 5
      is_product_restricted: this.isProductRestricted,
      special_conditions: this.specialConditions,
      storage_requirement: this.storageRequirement,
      hazardous_material_declaration: this.hazardousMaterialDeclaration,
      schedule_drug_category: this.scheduleDrugCategory,
      
      // Section 7 (Auto-filled)
      created_by: localStorage.getItem('emp_id') || '',
      created_date_time: new Date().toISOString(),
      ip_logged: '',
      
      // Section 8
      auto_renewal_reminder_before: this.autoRenewalReminderBefore,
      expiry_alert_toggle: this.expiryAlertToggle,
      notify_to: JSON.stringify(this.selectedNotifyUsers),
      
      // Section 9
      requires_internal_approval: this.requiresInternalApproval,
      approver1: this.approver1,
      approver2: this.approver2,
      approver3: this.approver3,
      approval_status: this.approvalStatus,
      approval_remarks: this.approvalRemarks
    };

    // Append form data
    for (let key in formData) {
      uploadData.append(key, formData[key]);
    }

    // Append files
    if (this.authorizationCopyFile) {
      uploadData.append('authorization_copy', this.authorizationCopyFile, this.authorizationCopyFile.name);
    }
    if (this.annexuresFile) {
      uploadData.append('annexures', this.annexuresFile, this.annexuresFile.name);
    }
    if (this.digitalSignatureFile) {
      uploadData.append('digital_signature', this.digitalSignatureFile, this.digitalSignatureFile.name);
    }

    this.service.post('exports/exports.php?type=saveAuthorization', uploadData).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Authorization saved successfully');
        this.resetForm(form);
      } else {
        alertify.error(response['status'] || 'An error occurred, please try again');
      }
    }, error => {
      console.error('Error saving authorization:', error);
      alertify.error('An error occurred, please try again');
    });
  }

  // Reset form
  resetForm(form: any) {
    form.resetForm();
    this.authorizationType = '';
    this.companyName = '';
    this.authorizationHolderName = '';
    this.designation = '';
    this.contactNumber = '';
    this.emailId = '';
    this.iecCode = '';
    this.gstTaxId = '';
    this.companyAddress = '';
    this.authorizationLicenseNumber = '';
    this.issuingAuthority = '';
    this.issueDate = '';
    this.validFromDate = '';
    this.validTillDate = '';
    this.status = 'Pending';
    this.productCategory = '';
    this.productName = '';
    this.hsCode = '';
    this.itcCode = '';
    this.drugLicenseNo = '';
    this.controlledSubstanceCheck = 'No';
    this.countryOfOrigin = '';
    this.countryOfDestination = '';
    this.portOfEntryExit = '';
    this.authorizedQuantity = 0;
    this.unit = '';
    this.usedQuantity = 0;
    this.remainingQuantity = 0;
    this.isProductRestricted = 'No';
    this.specialConditions = '';
    this.storageRequirement = '';
    this.hazardousMaterialDeclaration = 'No';
    this.scheduleDrugCategory = '';
    this.authorizationCopyFile = null;
    this.annexuresFile = null;
    this.digitalSignatureFile = null;
    this.autoRenewalReminderBefore = '30';
    this.expiryAlertToggle = 'Yes';
    this.selectedNotifyUsers = [];
    this.requiresInternalApproval = 'Yes';
    this.approver1 = '';
    this.approver2 = '';
    this.approver3 = '';
    this.approvalStatus = 'Pending';
    this.approvalRemarks = '';
  }





    countries: string[] = [
    "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra",
    "Angola", "Anguilla", "Antigua & Barbuda", "Argentina", "Armenia",
    "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas",
    "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium",
    "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia",
    "Bosnia & Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria",
    "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada",
    "Chile", "China", "Colombia", "Costa Rica", "Croatia",
    "Cuba", "Cyprus", "Czech Republic", "Denmark", "Dominican Republic",
    "Ecuador", "Egypt", "El Salvador", "Estonia", "Ethiopia",
    "Fiji", "Finland", "France", "Germany", "Greece",
    "Hong Kong", "Hungary", "Iceland", "India", "Indonesia",
    "Iran", "Iraq", "Ireland", "Israel", "Italy",
    "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya",
    "Kuwait", "Latvia", "Lebanon", "Lithuania", "Luxembourg",
    "Malaysia", "Maldives", "Malta", "Mexico", "Monaco",
    "Mongolia", "Morocco", "Myanmar", "Nepal", "Netherlands",
    "New Zealand", "Nigeria", "Norway", "Oman", "Pakistan",
    "Panama", "Peru", "Philippines", "Poland", "Portugal",
    "Qatar", "Romania", "Russia", "Saudi Arabia", "Singapore",
    "Slovakia", "Slovenia", "South Africa", "South Korea", "Spain",
    "Sri Lanka", "Sweden", "Switzerland", "Syria", "Taiwan",
    "Tanzania", "Thailand", "Trinidad & Tobago", "Tunisia", "Turkey",
    "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America",
    "Uruguay", "Uzbekistan", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
  ];










}

