import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-local',
  templateUrl: './local.component.html',
  styleUrls: ['./local.component.css']
})
export class LocalComponent implements OnInit {

  isView = false;
  results: any[] = [];
  gsts;
  po_qty=0;
  selectedResult = [];
  units;

  isChange = false;
  vendor_unit = '';
  weighing_procedure='Container';
  selectedLocation = [];

  remark = '';
  vendor;
  materials:any=[];

  vendor_type='';
  materialList=[];
  qty=0;
  challan_qty=0;
  vendorTypes;
  selectedVendor=[];
  
  rate=0;
  gst = 0;
 
  
  selectedUnit=[];
  selectedFile: File;
  gross_amount=0;
  tax_amount=0;
  net_amount=0;
  gstnos;
  vendors;
  tax_total=0;
  gross_total=0;
  gst_total = 0;
  net_total = 0;
  selectedMaterial=[];
  selectedManufacturer=[];

  unit='';
  unit1=[];
  today='';
  subtests;
  vendor_no ='';
  vendort;
  sevendor =[];
  vendorM;
  types;
  packtypes;
  Non_registration;
  isPassword= false;
  isUpload: number;
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getPendingChallans();
    this.getGstDetails();
    this.getMaterialType();
    this.getRawManufacturesr();
    this.getnonReg();
    this.getVendor();
    this.getMaterialType1();
  }



   onFileChanged(event) {
    if(event.target.files.length === 1) {
      this.selectedFile = event.target.files[0];
    }
  }

  getMaterialType(){
    this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
      this.types= response;
    });
  }
  getMaterialType1(){
    this.service.get('master/materialtype.php?type=getPackingMaterialtype1').subscribe(response => {
      this.packtypes= response;
    });
  }

  getPendingChallans(){
    this.service.get('store/challan.php?type=getPendingChallansLocal').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  getGstDetails(){
    this.service.get('common.php?type=getGST').subscribe(response => {
      this.gsts = response;
    });
  }


  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    // this.getVendorUnits();
  }

  selectLocation(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedLocation = this.units[index];
    }
  }

  // getVendorUnits() {
  //   this.service.get('purchase/vendor.php?type=getVendorUnit&vendor_no=' + this.selectedResult['vendor_no']).subscribe(response => {
  //     this.units = response;
  //   });
  // }

  updateChallan(status) {
    let temp=this.selectedResult;
    // temp['materials']=this.materialList;
    temp['remark'] = this.remark;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 

    if (this.selectedFile !== undefined) {
      uploadData.append('challan_file', this.selectedFile, this.selectedFile.name);
    }
   // uploadData.append('materials', this.materialList);

   temp['weighing_procedure'] = this.weighing_procedure;      
    this.service.post('store/challan.php?type=updateChallan&status=' + status + '&id=' + this.selectedResult['id'] +'&weighing_procedure=' + this.weighing_procedure+'&po_no='+this.selectedResult['po_no'],uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.remark = '';
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingChallans();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  vendor_reg;
  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
  
    let temp = data.value;
    temp['qty'] = this.qty;
    temp['rate'] = this.rate;
    temp['gst']= this.gst;
    console.log(temp['gst']);
    if( temp['material_code'].material_code != null &&  temp['material_code'].material_code!= undefined){
    temp['material_code'] = temp['material_code'].material_code;
    }else{
      temp['material_code'] = temp['material_code'];
    }
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['vendor_type'] = this.selectedVendor['vendor_type'];
    temp['vendor_name'] = this.selectedVendor['vendor_name'];
    temp['vendor_no'] = this.selectedVendor['vendor_no'];
    temp['address'] = this.selectedVendor['address'];
    
  
    if(this.vendor_reg=='Registration')
    {
      temp['manufacturer_no'] = this.selectedManufacturer['vendor_no'];
      temp['vendor_code'] = this.vendor_no;
      // temp['manufacturer_name'] = this.selectedManufacturer['vendor_name'];
      temp['vendor_name'] = this.vendor_no;
      temp['vendor_type'] =this.vendor_reg;// this.selectedVendor['vendor_type'];
      console.log(temp);
    }
    if(this.vendor_reg=='Non-Registration')
    {
      temp['vendor_type'] =this.vendor_reg;// this.selectedVendor['vendor_type'];
      temp['vendor_no'] = this.vendor_no;
      temp['manufacturer_no'] = this.selectedManufacturer['vendor_no'];
      temp['vendor_code'] = this.vendor_no;
      // temp['manufacturer_name'] = this.selectedManufacturer['vendor_name'];
      console.log(temp);
    }

    
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
  }



  
  del(index){
    let temp=this.materialList[index];
    this.gross_total=this.gross_total*1-temp['gross_amount']*1;
    this.gst_total=this.gst_total*1-temp['tax_amount']*1;
    this.net_total=this.net_total*1-temp['net_amount']*1;
    this.materialList.splice(index, 1);
  }




  calculation() {
    this.gross_total = this.challan_qty * this.rate;
    this.tax_total = (this.gross_total * +this.gst) / 100;
    this.net_total = this.gross_total + this.tax_total;
    this.net_total = +parseFloat(+this.net_total+'').toFixed(2);
    console.log(this.gst);
  }

  getGeneralMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype='+value+'&material_type='+this.selectedResult['material_type']).subscribe(response=>{
      this.materials = response;
      // this.selectedMaterial = [];
      for (let i = 0; i < this.materials.length; i++) {
        let material = this.materials[i];
        material['gross_total'] = 0;
        material['gst_total'] = 0;
        material['net_total'] = 0;
        material['qty'] = 0;
        material['rate'] = 0;
        this.materials[i] = material;
      }
    });
  }
  getVendor(){
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendors=response;
    });
  }
  getVendorType(){
    this.service.get('common.php?type=getVendorsByType&vendor_type='+this.vendor_type).subscribe(response=>{
      this.vendorTypes=response;
    });
  }


  getRawManufacturesr(){
    this.service.get('common.php?type=getRAWManufactures').subscribe(response=>{
      this.vendors=response;
    });
  }
  nonvendors;
  getnonReg(){
    this.service.get('store/challan.php?type=GET_Non_registration').subscribe(response=>{
      this.nonvendors=response;
    });
  }

  
 
 save(main_form: any, status: string) {
  const uploadData = new FormData();
  const temp1 = this.selectedResult;

  // ✅ Append all selectedResult fields
  for (let key in temp1) {
    if (temp1[key] !== undefined && temp1[key] !== null) {
      uploadData.append(key, temp1[key]);
    }
  }

  // ✅ File upload (optional)
  if (this.selectedFile) {
    uploadData.append('challan_file', this.selectedFile, this.selectedFile.name);
  }

  // ✅ Append extra fields
  uploadData.append('weighing_procedure', this.weighing_procedure || '');
  uploadData.append('remark', this.remark || '');
  uploadData.append('vendor_no', this.vendor_no || '');
  uploadData.append('materials', JSON.stringify(this.materialList || []));

  // ✅ Add type, status, id, po_no in URL params
  const url =
    'store/challan.php?type=local_purchase' +
    '&status=' + encodeURIComponent(status) +
    '&id=' + encodeURIComponent(this.selectedResult['id']) +
    '&weighing_procedure=' + encodeURIComponent(this.weighing_procedure || '') +
    '&po_no=' + encodeURIComponent(this.selectedResult['po_no'] || '');

  // ✅ POST the data
  this.service.post(url, uploadData).subscribe({
    next: (response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Record Inserted Successfully');
        this.isView = false;
      } else {
        console.error('Server Response:', response);
        alertify.error('Error: ' + (response['status'] || 'Unknown error'));
      }
    },
    error: (err) => {
      console.error('HTTP Error:', err);
      alertify.error('Request failed — check console.');
    }
  });
}


  // non_registration(value) {
  //   if (value == 'Non-Registration') {
  //     this.Non_registration = true;
  //   }
  // }
  vendor_name;
  add_ven(value) {
    if (value == 'Add New') {
      this.vendor_no = '';
      this.Non_registration = true;
    }else{
        this.vendor_name = value;
    }
  }
  vendorName(value) {
    this.vendor_name = value;
  }
   reg_pan;
reg_gst;
reg_address;
reg_vendor;
  saveregistration(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['reg_pan'] = this.reg_pan;
    temp['reg_gst'] = this.reg_gst;
    temp['reg_address'] = this.reg_address;
    temp['reg_vendor'] = this.reg_vendor;

      this.service.post('store/challan.php?type=Non_registration', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          this.Non_registration = false;
          alertify.success('Registration Done successfully');
          this.getnonReg();
        } 
        else {
          alertify.error(response['status']);
        }
      });
    
  }

}
