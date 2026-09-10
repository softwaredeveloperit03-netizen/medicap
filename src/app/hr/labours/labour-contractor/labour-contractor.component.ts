import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  CANADIAN_BANK_ADD_NEW,
  DEFAULT_CANADIAN_BANKS,
} from 'src/app/shared/canadian-banks';
declare let alertify;

const POSTAL_CA_PATTERN = /^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$/;

@Component({
  selector: 'app-labour-contractor',
  templateUrl: './labour-contractor.component.html',
  styleUrls: ['./labour-contractor.component.css']
})
export class LabourContractorComponent implements OnInit {

  clients;
  isView = false;
  isNew = false;
  entries;
  selectedEntry;
  departments;
  department_name ='';
  remark = '';
  configurations = [];
  isApprover;
  company = '';
  products = [];
 

  list;

  pf;
  esic_no;
  gst_no;
  labour_lic;

  postal_code = '';
  bank_name = '';
  transit_no = '';
  institution_no = '';
  acc_type = 'Checking Account';
  acc_no = '';

  readonly postalCaHtmlPattern = '^[A-Za-z][0-9][A-Za-z][ -]?[0-9][A-Za-z][0-9]$';
  canadianBanks: string[] = [...DEFAULT_CANADIAN_BANKS];
  readonly addNewBankOption = CANADIAN_BANK_ADD_NEW;
  showAddBankModal = false;
  newBankName = '';

  canadianProvinces: string[] = [
    'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
    'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
    'Quebec', 'Saskatchewan', 'Yukon'
  ];

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getContractorlist();
    this.loadCanadianBanks();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }
  }

  approveLabourCOntractor(status) {
    let temp = {};
    temp['id'] = this.selectedEntry.id;
    temp['status'] = status;
    this.service.post('admin.php?type=updateContractor', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.getContractorlist();
          this.isView = false;
          alertify.success('Labour '+status+' successfully');
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alertify.error('An error has occurred.');
        } else {
          alertify.error('An error has occurred, http status:' + error.status);
        }
      });
  }

  view(data) {
    this.selectedEntry = { ...data };
    if (!this.selectedEntry['acc_type']) {
      this.selectedEntry['acc_type'] = this.selectedEntry['i_no1'] || 'Checking Account';
    }
    this.isView = true;
    this.isEdit = false;
  }

  open(url) {
    url = this.service.url + '../../upload/contractor/doc/' + url;
    window.open(url, '_blank');  
  }
 

  isEdit = false;

  getContractorlist() {
    this.service.get('admin.php?type=getContractorlist').subscribe(response => {
      this.list = response;
    });
  }



  file_pf: File;
  file_esic_no: File;
  file_gst_no: File;
  file_labour_lic: File;
  file_labour_pri: File;
  file_other: File;

  onFileChange($event, name) {
    if (name == 'file_pf') {
      this.file_pf = $event.target.files[0];
    } else if (name == 'file_esic_no') {
      this.file_esic_no = $event.target.files[0];
    }  else if (name == 'file_gst_no') {
      this.file_gst_no = $event.target.files[0];
    }else if (name == 'file_labour_lic') {
      this.file_labour_lic = $event.target.files[0];
    }else if (name == 'file_labour_pri') {
      this.file_labour_pri = $event.target.files[0];
    }else if (name == 'file_other') {
      this.file_other = $event.target.files[0];
    }
   }

   uploadFile(fileType) {
 
      const formData = new FormData();
      formData.append('id', this.selectedEntry.id);
      formData.append('fileType', fileType);
      
      if (fileType == 'file_pf') {
        if (this.file_pf !== undefined) {
          formData.append('file_pf', this.file_pf, this.file_pf.name);
        }else{
          alertify.error('Please select a file to upload');
          return;
        }
      }
      else if (fileType == 'file_esic_no') {
        if (this.file_esic_no !== undefined) {
          formData.append('file_esic_no', this.file_esic_no, this.file_esic_no.name);
        }else{
          alertify.error('Please select a file to upload');
          return;
        }
      } 
      else if (fileType == 'file_gst_no') {
        if (this.file_gst_no !== undefined) {
          formData.append('file_gst_no', this.file_gst_no, this.file_gst_no.name);
        }else{
          alertify.error('Please select a file to upload');
          return;
        }
      } 
      else if (fileType == 'file_labour_lic') {
        if (this.file_labour_lic !== undefined) {
          formData.append('file_labour_lic', this.file_labour_lic, this.file_labour_lic.name);
        }else{
          alertify.error('Please select a file to upload');
          return;
        }
      } 
      else if (fileType == 'file_labour_pri') {
        if (this.file_labour_pri !== undefined) {
          formData.append('file_labour_pri', this.file_labour_pri, this.file_labour_pri.name);
        }else{
          alertify.error('Please select a file to upload');
          return;
        } 
      } 
      else if (fileType == 'file_other') {
        if (this.file_other !== undefined) {
          formData.append('file_other', this.file_other, this.file_other.name);
        }else{
          alertify.error('Please select a file to upload');
          return;
        } 
      } 
  
      this.service.post('admin.php?type=uploadFile', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.getContractorlist();
          alertify.success('Document Updated Successfully........');
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alertify.error('An error has occurred.');
        } else {
          alertify.error('An error has occurred, http status:' + error.status);
        }
      });
    }


   saveForm(form) {

      if (!form.valid) {
        alertify.error('Please fill all required fields');
        return;
      }

      if (!POSTAL_CA_PATTERN.test((this.postal_code || form.value.pincode || '').trim())) {
        alertify.error('Enter a valid Canadian postal code (e.g. K1A 0A6)');
        return;
      }

      const formData = new FormData();
      const temp = { ...form.value };

      temp['bank'] = this.bank_name;
      temp['branch'] = this.transit_no;
      temp['ifsc'] = this.institution_no;
      temp['ac_no'] = this.acc_no;
      temp['acc_type'] = this.acc_type;
      temp['pincode'] = this.postal_code || temp['pincode'];

      for (const key in temp) {
        if (key === 'aadhar_card' || key === 'photo') {
          continue;
        }
        formData.append(key, temp[key] == null ? '' : temp[key]);
      }

      if (this.file_pf !== undefined) {
        formData.append('file_pf', this.file_pf, this.file_pf.name);
      } 
      if (this.file_esic_no !== undefined) {
        formData.append('file_esic_no', this.file_esic_no, this.file_esic_no.name);
      } 
      if (this.file_gst_no !== undefined) {
        formData.append('file_gst_no', this.file_gst_no, this.file_gst_no.name);
      } 
      if (this.file_labour_lic !== undefined) {
        formData.append('file_labour_lic', this.file_labour_lic, this.file_labour_lic.name);
      } 
      if (this.file_labour_pri !== undefined) {
        formData.append('file_labour_pri', this.file_labour_pri, this.file_labour_pri.name);
      } 
      if (this.file_other !== undefined) {
        formData.append('file_other', this.file_other, this.file_other.name);
      } 


      this.service.post('admin.php?type=AddLabourContractor', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          form.resetForm();
          this.getContractorlist();
          this.isNew = false;
          alertify.success('Labour Contractor added successfully');
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alertify.error('An error has occurred.');
        } else {
          alertify.error('An error has occurred, http status:' + error.status);
        }
      });
    }



   updateLabourCOntractor(form) {

      if (!form.valid) {
        alertify.error('Please fill all required fields');
        return;
      }

      if (!POSTAL_CA_PATTERN.test((this.selectedEntry['pincode'] || '').trim())) {
        alertify.error('Enter a valid Canadian postal code (e.g. K1A 0A6)');
        return;
      }

      const temp = { ...form.value };
      temp['id'] = this.selectedEntry.id;
      temp['acc_type'] = this.selectedEntry['acc_type'] || temp['acc_type'];

      this.service.post('admin.php?type=updateLabourCOntractor', JSON.stringify(temp)).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          form.resetForm();
          this.getContractorlist();
          this.isView = false;
          alertify.success('Labour Updated successfully');
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alertify.error('An error has occurred.');
        } else {
          alertify.error('An error has occurred, http status:' + error.status);
        }
      });
  }
 
  downloadReport(){
     this.service.open('admin.php?type=getContractorlist_log_pdf');
  }

  formatPostalInput(event: Event): void {
    const el = event.target as HTMLInputElement;
    if (!el?.value) {
      return;
    }
    const v = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    let formatted = el.value;
    if (v.length >= 6) {
      formatted = `${v.slice(0, 3)} ${v.slice(3, 6)}`.trim();
    } else if (v.length > 3) {
      formatted = `${v.slice(0, 3)} ${v.slice(3)}`.trim();
    } else {
      formatted = v;
    }
    this.postal_code = formatted;
    if (el.value !== formatted) {
      el.value = formatted;
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  private getBankStorageKey(): string {
    const plantId = localStorage.getItem('plant_id') || 'default';
    return `hr_canadian_banks_custom_${plantId}`;
  }

  loadCanadianBanks(): void {
    let custom: string[] = [];
    try {
      const raw = localStorage.getItem(this.getBankStorageKey());
      if (raw) {
        custom = JSON.parse(raw);
      }
    } catch {
      custom = [];
    }
    this.canadianBanks = Array.from(new Set([...DEFAULT_CANADIAN_BANKS, ...custom]))
      .sort((a, b) => a.localeCompare(b));
  }

  onBankChange(value: string): void {
    if (value === CANADIAN_BANK_ADD_NEW) {
      if (this.selectedEntry) {
        this.selectedEntry['bank'] = '';
      } else {
        this.bank_name = '';
      }
      this.newBankName = '';
      this.showAddBankModal = true;
    }
  }

  closeAddBankModal(): void {
    this.showAddBankModal = false;
    this.newBankName = '';
  }

  saveNewBank(): void {
    const name = (this.newBankName || '').trim();
    if (!name) {
      alertify.error('Bank name is required.');
      return;
    }
    const exists = this.canadianBanks.some((b) => b.toLowerCase() === name.toLowerCase());
    if (exists) {
      const bank = this.canadianBanks.find((b) => b.toLowerCase() === name.toLowerCase()) || name;
      if (this.selectedEntry) {
        this.selectedEntry['bank'] = bank;
      } else {
        this.bank_name = bank;
      }
      this.closeAddBankModal();
      return;
    }
    try {
      const key = this.getBankStorageKey();
      const raw = localStorage.getItem(key);
      const custom = raw ? JSON.parse(raw) : [];
      custom.push(name);
      localStorage.setItem(key, JSON.stringify(custom));
    } catch {
      // ignore storage errors
    }
    this.loadCanadianBanks();
    if (this.selectedEntry) {
      this.selectedEntry['bank'] = name;
    } else {
      this.bank_name = name;
    }
    this.closeAddBankModal();
    alertify.success('Bank added successfully');
  }



  searchQuery;
  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.list; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.list.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }




}
