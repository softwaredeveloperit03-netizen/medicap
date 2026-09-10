import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-initiate-approval',
  templateUrl: './initiate-approval.component.html',
  styleUrls: ['./initiate-approval.component.css']
})
export class InitiateApprovalComponent implements OnInit {

  init = ({
    height: 300,
    menubar: true,
    content_style: 'body { font-size: 12pt; font-family: Times; }',
    plugins: [
      'advlist autolink lists link image charmap print preview anchor',
      'searchreplace visualblocks code fullscreen',
      'insertdatetime media table paste code help wordcount'
    ],
    toolbar:
      'formatselect | bold italic backcolor | \
      alignleft aligncenter alignright alignjustify | \
      bullist numlist outdent indent | removeformat | help'
  });
  api = 'lh5ymzb4rorw2zhscucerx1013ntad53j7jjnoiokc0pjg8v';
  
  isView = false;
  results;
  reference;

  departments;
  selectedResult = [];
  remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();
    this.getcheckedinitiation();
  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getcheckedinitiation() {
    this.service.get('sops.php?type=getcheckedinitiation').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.reference = this.selectedResult['reference'];
    this.isView = true;
  }

  update(status) {
    this.service.get('sops.php?type=approveinitiation&status=' + status + '&id=' + this.selectedResult['id'] + '&ctrl_no=' + this.selectedResult['ctrl_no'] + '&remark=' + this.remark).subscribe(response => {
      if (response['status'] === 'success') {
        this.isView = false;
        this.remark = '';
        this.getcheckedinitiation();
        alert('Updated Successfully');
      } else {
        alert('An error has occurred, please try again');
      }
    });
  }

}
