import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-contractor-agreement',
  templateUrl: './contractor-agreement.component.html'
})
export class ContractorAgreementComponent implements OnInit {

  clients;
  isView = false;
  isNew = false;
  entries;
  selectedEntry;
  isApprover;
  list;

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
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank')
  }

  getContractorlist() {
    this.service.get('admin.php?type=getApprovedContractor').subscribe(response => {
      this.list = response;
    });
  }

   saveForm(data) {
      const formData = new FormData();
      formData.append('person', data.value.person);
      formData.append('address', data.value.address);
      formData.append('pincode', data.value.pincode);
      formData.append('phone_no', data.value.phone_no);
      formData.append('email', data.value.email);

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
