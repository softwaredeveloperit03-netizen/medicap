import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {WebcamImage} from 'ngx-webcam';
import { DatePipe } from '@angular/common';

declare let alertify;
@Component({
  selector: 'app-direct-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]
})
export class NewComponent implements OnInit {
  po='';
  vendor;
  selectedVendor: any = null;
  departments;
  materials;
  materialList=[];
  qty=0;
  vendors;
  rate=0;
  material_name = '';
  selectedFile: File;
  gross_amount=0;
  tax_amount=0;
  net_amount=0;
  isUpload=0;
  tax_total=0;
  gross_total :any=0;
  gst_total:any=0;
  net_total :any=0;
  selectedMaterial=[];
  isRaw=false;
  isPacking=false;
  isPhoto = false;
  selectedCountry = '';
  isfinish = false;
  transports;
  gst='';
  gsts;
  vendorTypes;
  vendorM;
  vendor_type;
  today='';
  selectedManufacturer=[];
  transport;
  public webcamImage: WebcamImage = null;

  handleImage(webcamImage: WebcamImage) {
    this.webcamImage = webcamImage;
  }

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getVendor();
    this.getTransport();
    this.getGsts();
    this.getVendorType();
    this.getVendorMS();
    this.getDepartments();
    this.getpassno();
  }

  getVendor(){
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendors=response;
    });
  }

  onVendorChange(vendorNo: string) {
    if (!vendorNo || !this.vendors) {
      this.selectedVendor = null;
      return;
    }
    this.selectedVendor = this.vendors.find((v) => v.vendor_no === vendorNo) || null;
  }

  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.departments=response;
    });
  }

  getVendorType(){
    this.service.get('common.php?type=getVendorsByType&vendor_type='+this.vendor_type).subscribe(response=>{
      this.vendorTypes=response;
    });
  }

    getVendorMS(){
    this.service.get('common.php?type=getManufactures').subscribe(response=>{
      this.vendorM=response;
    });
  }
  getGsts(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gsts=response;
    });
  }

  getTransport(){
    this.service.get('master/transport.php?type=getTransport').subscribe(response=>{
      this.transports = response;
    });
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUpload = 1;
  }

  gatepassno;
  getpassno(){
    this.service.get('common.php?type=returanable_gatepassNo').subscribe(response=>{
      this.gatepassno=response;
    });
  }

   getgetpassdet(index){
  index =  index-1;
    this.materialList =  this.gatepassno[index]['materials'];
  }

  save(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    if (!this.material_name || !String(this.material_name).trim()) {
      alertify.error('Material Name is required');
      return;
    }
  
    let temp=data.value;
    const vendorNo = temp['vendor_no'] || (this.selectedVendor && this.selectedVendor['vendor_no']);
    if (!vendorNo) {
      alertify.error('Please select a vendor!');
      return;
    }
    temp['vendor_no'] = vendorNo;
    const vendor = this.vendors?.find((v) => v.vendor_no === vendorNo) || this.selectedVendor;
    if (vendor) {
      temp['vendor_name'] = vendor.vendor_name;
    }
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
       uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('challan_file', this.selectedFile, this.selectedFile.name);
    }

    const materials = [{
      material_name: String(this.material_name).trim(),
      material_code: '',
      qty: '0',
      rate: '0',
      gst: '0',
      gross_total: '0',
      gst_total: '0',
      net_total: '0',
      vendor_no: vendorNo
    }];
    
    uploadData.append('materials',JSON.stringify(materials));
    uploadData.append('material_name', String(this.material_name).trim());
     uploadData.append('gross_total', String(this.gross_total || 0));
     uploadData.append('gst_total', String(this.gst_total || 0));
     uploadData.append('net_total', String(this.net_total || 0));
     uploadData.append('type','Local');
    this.service.post('security/inward.php?type=saveDirectChallan',uploadData).subscribe({
      next: (response: any) => {
        if(response && response['status']=='success'){
            alertify.success('Saved successfully. Sent for approval.');
            data.resetForm();
            this.material_name = '';
            this.materialList = [];
        }else{
          alertify.error((response && response['status']) || 'Save failed');
        }
      },
      error: () => {
        alertify.error('Server error while saving. Please try again.');
      }
    });
  }

}
