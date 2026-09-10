import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-initiate-log',
  templateUrl: './initiate-log.component.html',
  styleUrls: ['./initiate-log.component.css']
})
export class InitiateLogComponent implements OnInit {

  init = ({
    height: 300,
    readonly: 1,
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
    this.service.get('sops.php?type=getinitiationlog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

}
