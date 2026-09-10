import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-draft',
  templateUrl: './draft.component.html',
  styleUrls: ['./draft.component.css']

})

export class DraftComponent implements OnInit {

   

  departments;
  department = localStorage.getItem('department');
  
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
    this.department = localStorage.getItem('department');

  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
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
  
    this.service.post('sops.php?type=saveExistingSopFile', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.resetForm();
        this.router.navigate(['/qa/qms/sops']);
       } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
