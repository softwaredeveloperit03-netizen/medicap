import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-newreceive',
  templateUrl: './newreceive.component.html',
  styleUrls: ['./newreceive.component.css']
})
export class NewreceiveComponent implements OnInit {
  isView = false;

  results;
  selectedReport = [];
 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingGRN();
    this.getVendor();
    this.getMediaMaster();
   }

   getMediaMaster() {
    this.service.get('master/media.php?type=getMedia').subscribe((response) => {
      this.medias = response;
    });
  }
  medias;
  material_subtype = 'Media';
  
  getPendingGRN() {
    this.service.get('qc/chemical.php?type=getPendingGRN&material_subtype='+this.material_subtype).subscribe(response => {
      this.results = response;
    });
  }
  getVendor() {

    this.service.get('microbiology/media.php?type=getApprovedVendor&vendor_type=Manufacturer').subscribe(response => {
      this.Manufacturers = response;
    });

    this.service.get('microbiology/media.php?type=getApprovedVendor&vendor_type=Supplier').subscribe(response => {
      this.Suppliers = response;
    });

  }
  Manufacturers;
  Suppliers;
  specifVol = 1000;
  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  selecteMedia(index){
    this.unit = this.medias[index-1].unit;
  }

  unit ='';
  
  flag = false;
  flag1 = false;
  flag2 = false;
  flag3 = false;

   

  onValueChange1(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag = false;
    } else {
      this.flag = true;
    }
  }


  selectedFile: File;
  isUpload = 0;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUpload = 1;
  }



  onValueChange3(newValue: string) {
    const receivedQty = /^\d+$/;
    const isValidReceivedQty = receivedQty.test(newValue);

    if (isValidReceivedQty) {
      this.flag2 = false;
    } else {
      this.flag2 = true;
    }
  }
  onValueChange4(newValue: string) {
    const receivedQty = /^\d+$/;
    const isValidReceivedQty = receivedQty.test(newValue);

    if (isValidReceivedQty) {
      this.flag3 = false;
    } else {
      this.flag3 = true;
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
    if (this.selectedFile !== undefined) {
      uploadData.append('signture', this.selectedFile, this.selectedFile.name);
    }

    uploadData.append('balance_qua', this.selectedReport['qty']);
    uploadData.append('consume_qua', '0');
    uploadData.append('media_stock', 'NA');

    this.service
      .post('microbiology/media.php?type=saveMediaStock&id='+this.selectedReport['id'], uploadData)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isView = false;
          this.getPendingGRN();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
  save1(data) {
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
      uploadData.append('signture', this.selectedFile, this.selectedFile.name);
    }

 

    this.service
      .post('microbiology/media.php?type=saveMediaStock&id='+this.selectedReport['id'], uploadData)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isView = false;
          this.getPendingGRN();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }




}
