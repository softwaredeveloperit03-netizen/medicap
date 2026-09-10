import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
// import { SupportAccessService } from '../support-access.service';
import { SupportAccessService } from '../support-access.service';

declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  photo: File;
  isUpload = false;
  plant_type;
  plant_name;
  department;
  loger_id;
  constructor( private router: Router,public service: SupportAccessService) { 
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_name = this.service.getPlantConfigFields('plant_name');
    this.loger_id = localStorage.getItem('loger_id');
        this.department = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_name = this.service.getPlantConfigFields('plant_name');
    this.loger_id = localStorage.getItem('loger_id');
        this.department = localStorage.getItem('department');

  }

  onFileChanged(event) {
    if (event.target.files.length > 0) {
      this.photo = event.target.files[0];
      this.isUpload = true;
    } else {
      this.isUpload = false;
    }
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.photo !== undefined) {
      uploadData.append('errorphoto', this.photo, this.photo.name);
    }
    
    this.service.post('support.php?type=saveQuery', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('You Ticket Has Been Raised successfully Please Wait Our Team Will Review Your Ticket And respond.');
        data.resetForm();
        this.plant_type = this.service.getPlantConfigFields('plant_type');
        this.plant_name = this.service.getPlantConfigFields('plant_name');
        this.router.navigate(['/support'])
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}


