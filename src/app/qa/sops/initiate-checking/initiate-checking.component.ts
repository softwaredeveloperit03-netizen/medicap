import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-initiate-checking',
  templateUrl: './initiate-checking.component.html',
  styleUrls: ['./initiate-checking.component.css']
})
export class InitiateCheckingComponent implements OnInit {

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

  departments;
  selectedResult = [];
  reference = [];
  remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();
    this.getpendinginitiation();
  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getpendinginitiation() {
    this.service.get('sops.php?type=getpendinginitiation').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.reference = this.selectedResult['reference'];
    this.isView = true;
  }

  update(status) {
    this.service.get('sops.php?type=checkinitiation&status=' + status + '&id=' + this.selectedResult['id'] + '&remark=' + this.remark + '&ctrl_no=' + this.selectedResult['ctrl_no']).subscribe(response => {
      if (response['status'] === 'success') {
        this.isView = false;
        this.remark = '';
        this.getpendinginitiation();
        alert('Updated Successfully');
      } else {
        alert('An error has occurred, please try again');
      }
    });
  }

}
