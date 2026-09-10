import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-stock-register',
  templateUrl: './stock-register.component.html',
  styleUrls: ['./stock-register.component.css']
})
export class StockRegisterComponent implements OnInit {

 
  isView = false;
  isNew = false;
  selectedEntry;
  isApprover;
  isChecker;
  list;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getStationarylist();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }

     if (localStorage.getItem('checker') === 'true') {
      this.isChecker = true;
     } else {
      this.isChecker = false;
     }
  }

  update(status) {
    this.service.get('admin.php?type=updateStationary&id=' + this.selectedEntry.id +
    '&status=' + status).subscribe(response => {
      alert('Updated Successfully');
      this.isView = false;
      this.getStationarylist();
    });
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank');
  }

  getStationarylist() {
    this.service.get('admin.php?type=getStationarylist').subscribe(response => {
      this.list = response;
    });
  }

   saveForm(data) {
      const formData = new FormData();

      formData.append('stationary', data.value.stationary);
      formData.append('no_required', data.value.no_required);
      formData.append('company', data.value.company);
      formData.append('details', data.value.details);

      this.service.post('admin.php?type=AddStationaryData', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getStationarylist();
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
    this.router.navigate(['/stationary-dashboard']);
  }


}
