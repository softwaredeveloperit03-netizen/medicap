import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-resources',
  templateUrl: './resources.component.html',
  styleUrls: ['./resources.component.css']
})
export class ResourcesComponent implements OnInit {
  formopen = false;
  form;
  list;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getResourcelist();
  }
  getResourcelist() {
    this.service.get('admin.php?type=getResourcelist').subscribe((response: any) => {
      this.list = response;
    });
  }
  submit(form) {
    if (form.valid) {
      this.service.post('admin.php?type=addResource', JSON.stringify(form.value)).subscribe(response => {
        if (response['status'] === 'success') {
          alert('Record Inserted Successfully');
          this.getResourcelist();
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
