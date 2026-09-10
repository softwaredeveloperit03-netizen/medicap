import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

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
    this.service.get('sops.php?type=getPendingSOPPreparation').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  save() {
    this.service.post('sops.php?type=prepareSOP', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('SOP Created Successfully');
        this.isView = false;
        this.getpendinginitiation();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
