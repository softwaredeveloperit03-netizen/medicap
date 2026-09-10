import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  isBranch = false;
  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;
  selectedFile5: File;
  states;
  state_name;
  unitList = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    
    this.getState();
  }

 
  addVendor(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const uploadData = new FormData();

    let temp = data.value;
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile1 !== undefined) {
      uploadData.append('gst_certificate', this.selectedFile1, this.selectedFile1.name);
    }
    if (this.selectedFile2 !== undefined) {
      uploadData.append('gst_certificate', this.selectedFile2, this.selectedFile2.name);
    }
    if (this.selectedFile3 !== undefined) {
      uploadData.append('gst_certificate', this.selectedFile3, this.selectedFile3.name);
    }
    if (this.selectedFile4 !== undefined) {
      uploadData.append('gst_certificate', this.selectedFile4, this.selectedFile4.name);
    }
    if (this.selectedFile5 !== undefined) {
      uploadData.append('gst_certificate', this.selectedFile5, this.selectedFile5.name);
    }
    uploadData.append('units', JSON.stringify(this.unitList));
    this.service.post('vendor.php?type=saveVendor', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Record Inserted successfully');
        this.router.navigate(['/vendor/registration']);
        this.unitList = [];
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }

  onFileChanged(event, value) {
    if(event.target.files.length > 0) {
      if (value == 'gst_certificate') {
        this.selectedFile1 = event.target.files[0];
      }
      if (value == 'mfg_lic_file') {
        this.selectedFile2 = event.target.files[0];
      }
      if (value == 'supplier_lic') {
        this.selectedFile3 = event.target.files[0];
      }
      if (value == 'incorporation_certificate') {
        this.selectedFile4 = event.target.files[0];
      }
      if (value == 'pan_card') {
        this.selectedFile5 = event.target.files[0];
      }
    } else {
      if (value == 'gst_certificate') {
        this.selectedFile1 = undefined;
      }
      if (value == 'mfg_lic_file') {
        this.selectedFile2 = undefined;
      }
      if (value == 'supplier_lic') {
        this.selectedFile3 = undefined;
      }
      if (value == 'incorporation_certificate') {
        this.selectedFile4 = undefined;
      }
      if (value == 'pan_card') {
        this.selectedFile5 = undefined;
      }
    }
  }

  getState(){
    this.service.get('common.php?type=getStates').subscribe(response =>{
      this.states=response;
    });
  }

  close() {
    this.router.navigate(['/']);
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.unitList[this.unitList.length] = data.value;
    data.resetForm();
    this.isBranch = false;
  }

}
