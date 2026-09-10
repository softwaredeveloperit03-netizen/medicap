import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-complaint',
  templateUrl: './complaint.component.html',
  styleUrls: ['./complaint.component.css']
})
export class ComplaintComponent implements OnInit {
  formopen = false;
  complaintlist = [];
  clientform;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getcomplaintlist();
  }
  getcomplaintlist() {
    this.service.get('admin.php?type=getComplaintlist').subscribe((response: any) => {
      this.complaintlist = response;
    });
  }
  submit(form) {
    if (form.valid) {
      this.service.post('admin.php?type=addComplaint', JSON.stringify(form.value)).subscribe(response => {
        if (response['status'] === 'success') {
          alert('Record Inserted Successfully');
          this.getcomplaintlist();
          this.formopen = false;
        } else {
          alert('Please try Again');
        }
      });
    } else {
      alert('Enter Correct Data');
    }
  }
  addclientbtn() {
    this.formopen = true;
  }
  closeclientbtn() {
    this.formopen = false;
  }

}
