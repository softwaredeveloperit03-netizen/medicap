import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-upload',
  templateUrl: './upload.component.html',
  styleUrls: ['./upload.component.css']
})
export class UploadComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  selectedFile: File;
  isupload = false;
  yield_qty = 0;
  pack_qty = 0;
  pack_unit = '';
  exp_date='';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getActiveBatches();
  }

  getActiveBatches() {
    this.service.get('production/bmr/manufacturing.php?type=getActiveBatches').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  onFileChanged(event) {
    if (event.target.files.length > 0) {
      this.selectedFile = event.target.files[0];
      this.isupload = true;
    } else {
      this.isupload = false;
    }
  }

  upload() {
    const uploadData = new FormData();
    if (this.isupload == true) {
      uploadData.append('bmrfile', this.selectedFile, this.selectedFile.name);
    } else {
      alertify.error('File is required!');
      return;
    }
    uploadData.append('product_code', this.selectedResult['product_code']);
    uploadData.append('complete_date', this.selectedResult['complete_date']);
    uploadData.append('batch_no', this.selectedResult['batch_no']);
    uploadData.append('batch_size', this.selectedResult['batch_size']);
    uploadData.append('yield_qty', this.yield_qty + '');
    uploadData.append('pack_qty', this.pack_qty + '');
    uploadData.append('pack_unit', this.pack_unit + '');
    uploadData.append('exp_date', this.exp_date +'');
    this.service.post('production/bmr/manufacturing.php?type=uploadBMR&id='+this.selectedResult['id'], uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Record Added Successfully');
        this.getActiveBatches();
        this.isupload = false;
        this.isView = false;
      } else {
        alertify.error('some error occured!');
      }
    });
  }

}
