import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-master',
  templateUrl: './master.component.html',
  styleUrls: ['./master.component.css']
})
export class MasterComponent implements OnInit {

  standards;
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getmedia();
  }
  selectedFile: File;
  isUpload=0;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUpload = 1;
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
    if (this.selectedFile !== undefined) {
      uploadData.append('coa', this.selectedFile, this.selectedFile.name);
    }
    this.service.post('master/media.php?type=saveMasterMedia', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.getmedia();
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }
  getmedia() {
    this.service.get('master/media.php?type=getMedia').subscribe(response => {
      this.standards = response;
    });
  }
  openlic(file) {
    if (file !== '') {
      window.open(this.service.url + 'upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }

  
  download() {
    this.service.open('master/media.php?type=downloadMedia')
  }


}
