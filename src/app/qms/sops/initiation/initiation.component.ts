import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-initiation',
  templateUrl: './initiation.component.html',
  styleUrls: ['./initiation.component.css']
})
export class InitiationComponent implements OnInit {

 

  department;
  
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
     this.department = localStorage.getItem('department');
  }

 


  sopFile: File;



  onFileChanged(event) {
    this.sopFile = event.target.files[0];
  }
 

  save(data) {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
 
    let temp = data.value;
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
 
    if (this.sopFile !== undefined) {
      uploadData.append('sopFile', this.sopFile, this.sopFile.name);
    } 
 
  
    this.service.post('sops.php?type=saveinitiation', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.resetForm();
       } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
