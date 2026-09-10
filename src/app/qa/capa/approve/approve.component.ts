import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html'
})
export class ApproveComponent implements OnInit {
  isView = false;
  results = [];

  selectedResult = [];

  isUpload = false;
  files:string  []  =  [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getCheckedCAPA();
  }

  getCheckedCAPA() {
    this.service.get('qa/capa.php?type=getCheckedCAPA').subscribe((response:any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  checkUpload(value) {
    if (value == 'Yes') {
      this.isUpload = true;
    } else {
      this.isUpload = false;
    }
  }

  onFileChange(event)  {
    for  (var i =  0; i <  event.target.files.length; i++)  {  
        this.files.push(event.target.files[i]);
    }
  }

  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    if (this.files.length == 0) {
      alert('Upload Documents');
      return;
    }
    data = data.value;
    data['capa_no'] = this.selectedResult["capa_no"];

    const formData =  new  FormData();

    Object.keys(data).forEach(key => {
      let value = data[key];
      formData.append(key, value);
    });

    formData.append("files", this.files.length + '');

    for  (var i =  0; i <  this.files.length; i++)  {
      formData.append("file-" + i,  this.files[i]);
    }
    
    this.service.post('qa/capa.php?type=updateCheckedCAPA', formData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.files=  [];
        this.isUpload = false;
        this.isView = false;
        this.getCheckedCAPA();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
