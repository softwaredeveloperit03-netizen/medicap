import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-labour-contractor',
  templateUrl: './labour-contractor.component.html'
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
  // tslint:disable-next-line: variable-name
  company = '';
  products = [];
  file: File;
  file_pf: File;
  file_esic_no: File;
  file_gst_no: File;
  file_labour_lic: File;

  list;

  pf;
  esic_no;
  gst_no;
  labour_lic;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getContractorlist();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }
  }

  updateContractor(action) {
    this.service.get('admin.php?type=updateContractor&id=' + this.selectedEntry.id +
    '&action=' + action).subscribe(response => {
      alert('Updated Successfully');
      this.isView = false;
      this.getContractorlist();
    });
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.pf = this.service.url + this.selectedEntry['file_pf'];
    this.esic_no = this.service.url + this.selectedEntry['file_esic_no'];
    this.gst_no = this.service.url + this.selectedEntry['file_gst_no'];
    this.labour_lic = this.service.url + this.selectedEntry['file_labour_lic'];

    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank')
  }

  getContractorlist() {
    this.service.get('admin.php?type=getContractorlist').subscribe(response => {
      this.list = response;
    });
  }

  onFileChange($event, name) {
    if (name == 'pf') {
      this.file_pf = $event.target.files[0];
    } else if (name == 'esic_no') {
      this.file_esic_no = $event.target.files[0];
    }  else if (name == 'gst_no') {
      this.file_gst_no = $event.target.files[0];
    } else if (name == 'labour_lic') {
      this.file_labour_lic = $event.target.files[0];
    }
   }

   saveForm(data) {
      const formData = new FormData();
      formData.append('contract_firm', data.value.contract_firm);
      formData.append('person', data.value.person);

      formData.append('address', data.value.address);
      formData.append('place', data.value.place);
      formData.append('landmark', data.value.landmark);
      formData.append('pincode', data.value.pincode);

      formData.append('phone_no', data.value.phone_no);
      formData.append('email', data.value.email);

      formData.append('file_pf', this.file_pf, this.file_pf.name);
      formData.append('file_esic_no', this.file_esic_no, this.file_esic_no.name);
      formData.append('file_gst_no', this.file_gst_no, this.file_gst_no.name);
      formData.append('file_labour_lic', this.file_labour_lic, this.file_labour_lic.name);

      formData.append('capacity', data.value.capacity);
      formData.append('bank', data.value.bank);
      formData.append('branch', data.value.branch);

      formData.append('ac_no', data.value.ac_no);
      formData.append('ifsc', data.value.ifsc);

      this.service.post('admin.php?type=AddLabourContractor', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getContractorlist();
          this.isNew = false;
          alert('Successfully Send for Approval');
        } else {
          alert('An error has occurred, please try again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      });
    }

  close() {
    this.router.navigate(['/labour-dashboard']);
  }

}

