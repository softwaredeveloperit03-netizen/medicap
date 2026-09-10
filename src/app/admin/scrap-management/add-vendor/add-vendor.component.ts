import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-add-vendor',
  templateUrl: './add-vendor.component.html',
  styleUrls: ['./add-vendor.component.css']
})
export class AddVendorComponent implements OnInit {
  isBranch = false;
  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;
  selectedFile5: File;
  states;
  state_name;
  unitList = [];
  vendor_type = 'Manufacturer';

  manufacturers;
  corporates: any;
  tempflat_no: any;
  temp_country: any;
  temp_state: any;
  temp_city: any;
  temp_mobile: any;
  temp_contact: any;
  temp_telephone: any;
  temp_pincode: any;
  permanent_flat: any;
  permanent_country: any;
  permanent_city: any;
  permanent_pincode: any;
  permanent_mobile_no: any;
  permanent_telephone: any;
  permanent_contact: any;
  permanent_state: any;
  isShown=false;
  additionals=[];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getState();
  }

  addAddtional(form){
    this.additionals.push(form.value);
  }

  toggleShow(){
    this.isShown = true;
  }
  checkAddress(value) {
    if (value) {
      this.tempflat_no =this.permanent_flat;
      this.temp_country = this.permanent_country;
      this.temp_state=  this.permanent_state ;
      this.temp_city= this.permanent_city;
      this.temp_pincode=  this.permanent_pincode;
      this.temp_mobile = this.permanent_mobile_no ;
      this.temp_telephone =  this.permanent_telephone ;
      this.temp_contact =  this.permanent_contact;
    } else {
      this.tempflat_no = '';
      this.temp_country = '';
      this.temp_state = '';
      this.temp_city = '';
      this.temp_pincode = '';
      this.temp_mobile = '';
      this.temp_telephone = '';
      this.temp_contact = '';
    }
  }

  addCorporate(form){
    this.corporates.push(form.value);
  }

  delCorporate(val){
    this.corporates.splice(val, 1);
  }

  checkType(value) {
    if (value == 'Supplier') {
      this.service.get('common.php?type=getManufacturers').subscribe(response => {
        this.manufacturers = response;
      });
    }
  }

 
  addVendor(data) {
    if (!data.valid) {
      alert('All fields are required');
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
      uploadData.append('mfg_lic_file', this.selectedFile2, this.selectedFile2.name);
    }
    this.service.post('vendor.php?type=saveVendor', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Record Inserted successfully');
        this.router.navigate(['/vendor']);
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
      alert('All fields are required');
      return;
    }
    this.unitList[this.unitList.length] = data.value;
    data.resetForm();
    this.isBranch = false;
  }
}
/* 
  isBranch = false;

  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;
  selectedFile5: File;
  states;
  gstnos;
  pans;
  unitList = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.service.observableState.subscribe(response => {
      this.states = response;
    });

    this.service.observableVendor.subscribe(response => {
      this.vendors = response;
    });
    this.getGSTNos();
  }

  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }

  getGSTNos(){
    this.service.get('purchase/vendor.php?type=getGSTNos').subscribe(response=>{
      this.gstnos=response['gstno'];
      this.pans=response['pan_no'];
    });
  }
  addVendor(data) {
    if (!data.valid) {
      alertify.error('Please enter mandatory fields!');
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
    this.service.post('purchase/vendor.php?type=saveVendor', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Record Inserted successfully');
        this.router.navigate(['/vendor']);
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

  close() {
    this.router.navigate(['/vendor']);
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

  vendors;

  selectedVendor: any[];
  filteredVendor: any[];
  filtereVendor(event) {
    let filtered =[];
    let query = event.query;
    for (let i = 0; i < this.vendors.length; i++) {
      let country = this.vendors[i];
      if (country.vendor_name.toLowerCase().indexOf(query.toLowerCase()) == 0) {
        filtered[filtered.length] = country;
      }
    }
    this.filteredVendor = filtered;
  }

  clearVendor() {
    alertify.error('Vendor Already Exist!');
    this.selectedVendor = [];
  }

  gst_no = '';
  checkGSTNo(value) {
    for (let i = 0; i < this.gstnos.length; i++) {
      let data = this.gstnos[i];
      if (data['gst_no'] == value) {
        this.gst_no = '';
        alertify.error('GST No. Already Exist!');
      }
    }
  }

  pan_no = '';
  checkPANNo(value) {
    for (let i = 0; i < this.pans.length; i++) {
      let data = this.pans[i];
      if (data['pan_no'] == value) {
        this.pan_no = '';
        alertify.error('PAN No. Already Exist!');
      }
    }
  }

}
 */ 