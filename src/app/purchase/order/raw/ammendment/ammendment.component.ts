import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-ammendment',
  templateUrl: './ammendment.component.html',
  styleUrls: ['./ammendment.component.css']
})
export class AmmendmentComponent implements OnInit {

  tax = ''
  currency='';
  isView = false;
  units;
  gst_applicable = 'Yes';
  vendor_selected = '';
  results;
  department = '';
  gst = 0;
  selectedResult: [];
  selectedResult1: [];
  isQuatation = false;
  materials = [];
  highest = [];
  gsts;
  order_type ='';
  list = [];
  lowest = [];
  tests = [];
  tests1 = [];
  vendors;
  isVendor = false;
  isProceed = false;
  
  selectedMaterial = {};
  item = [];
  departments;
  gstObj;
  tax_percent = 0;
  purchase_type = '';
  isAddQuotation = false;
  quotation_type = 'Local Purchase'
  selectedVendor;
    emp_id: string;
    isDIGI= false
    status: any;
  orders: any[] = [];
  loading = false;
   constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.AllRecord();
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
     this.getDepartments();
    this.getGst();
    this.getVendors();
  }


  viewf(){
    this.isView=false;
 
    
  }
  AllRecord(){
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getPoForAmendment').subscribe({
      next: (response: any) => {
        // API may return array OR {statuse/status: error message} on SQL failure
        if (Array.isArray(response)) {
          this.orders = response;
        } else {
          this.orders = [];
          const errMsg = (response && (response.statuse || response.status || response.message)) || 'Failed to load POs for amendment';
          if (String(errMsg).toLowerCase().indexOf('success') === -1) {
            alertify.error(typeof errMsg === 'string' ? errMsg.split('\n')[0] : 'Failed to load POs for amendment');
          }
        }
        this.loading = false;
      },
      error: () => {
        this.orders = [];
        this.loading = false;
        alertify.error('Failed to load POs for amendment');
      }
    });
   }


  getIndentForAmendments(indend_no,po_no){
    this.service.get('purchase/indent.php?type=getIndentForAmendments&indend_no='+indend_no+'&po_no='+po_no).subscribe((response : any) => {
      this.selectedResult = response[0];
       this.materials = this.selectedResult['materials'];
      this.order_type=this.selectedResult['order_type'];
      this.currency=this.selectedResult['currency'];
      this.purchase_type=this.selectedResult['purchase_type'];
      this.tax=this.selectedResult['tax'];
  
  
      this.isView = true;
    });
   }

selectedPo =[];
    viewOrder(index){

  
      this.selectedPo = this.filteredMaterials[index];

      console.log(this.selectedPo);
    

    
    this.getIndentForAmendments(this.selectedPo['indent_no'],this.selectedPo['po_no']);
   }



   
  searchQuery = '';

  get filteredMaterials(): any[] {
    const list = Array.isArray(this.orders) ? this.orders : [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return list;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return list.filter(material => {
      return Object.entries(material || {}).some(([key, value]) => {
        if (value == null || value === '') {
          return false;
        }
        if (key === 'entry_date' || key === 'approve_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && !isNaN(dateValue.getTime())
            && dateValue.toISOString().slice(0, 10).includes(query);
        }
        return value.toString().toLowerCase().includes(query);
      });
    });
  }



  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
 
 

 

  confirm() {
    this.isProceed = true;
  }

  actionIndend(value, index) {
    let materials = this.selectedResult['materials'];
    materials[index].status = value;
    this.selectedResult['materials'] = materials;
  }



  getGst() {
    this.service.get('common.php?type=getGST').subscribe(response => {
      this.gsts = response;
    });
  }

  approveIndend(status) {
    let materials = [];
    let final_request_object = [];
    let material_type = '';



    console.log(this.materials);
    for (let i = 0; i < this.materials.length; i++) {
      if (material_type != this.materials[i]['material_type'] && this.materials[i]['check']) {

        let obj = { "material_type": this.materials[i]['material_type'], materials: [] };
        final_request_object.push(obj);
        material_type = this.materials[i]['material_type'];
        
      }

    }

    if(final_request_object.length == 0){
      alertify.error('Failed: Please select material to proceed!');
      return;
    }


    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      if (material['check']) {
        if (status != 'reject') {
          if (material['order_qty'] == '' || material['order_qty'] == '0') {
            alertify.error('Failed: Please enter order qty!');
            return;
          }
          if (material['gross_total'] == '' || material['gross_total'] == '0') {
            alertify.error('Failed: Please select Vendor!');
            return;
          }
          if (Number.isNaN(material['gst_total'])) {
            alertify.error('Failed: Please select GST!');
            return;
          }

        }
        material['materials'] = null;
        material['lowest'] = null;
        material['vendors'] = null;  
        material['highest'] = null;
        material['order_type'] =  this.order_type;
        material['tax_type'] = this.tax;
        material['currency'] = this.currency;
        materials[materials.length] = material;


 




        for (let k = 0; k < final_request_object.length; k++) {
           
          
          if (final_request_object[k]['material_type'] == material['material_type']) {
            final_request_object[k]['materials'].push(material);
          }
        }
      }
    }

    console.log(final_request_object);

    this.service.post('purchase/indent.php?type=ReviseapproveIndend&status=' + status +'&po_id='+this.selectedPo['po_id']+'&po_no='+this.selectedPo['po_no'], JSON.stringify(final_request_object)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.AllRecord();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.loginPassward ='';
        this.approveIndend(this.status)

      

      }
      else
      {
        alertify.error('Digi-Sign Not Verified');

      }
    });
  }
  


  filterItem() {
    this.item = this.results;
   
  }

  viewQuataion(index) {
    this.materials = this.selectedResult['materials'];
    this.selectedMaterial = this.materials[0];
    this.highest = this.materials[index];
    this.tests = this.highest['highest']
    this.tests1 = this.highest['lowest']
    this.isQuatation = true;
  }

  selectQuotation(index, index1, value) {
    index = index - 1;
     if (value == undefined || value == "" ) {
      value = 0;
    }
    let material = this.materials[index1];
    let vendors = material['vendors'];
    let vendor = vendors[index];
    material['quotation_amt'] = vendor['quotation_amt'];
    material['quotation_no'] = vendor['quotation_no'];
    material['pack_size'] = vendor['pack_size'];
    material['vendor_no'] = vendor['vendor_no'];
    this.materials[index1] = material;
    //  let quot_materils_json = JSON.parse(vendor['materials'])
    material['gst_per'] = vendor['gst_per'];
    material['gst_id'] = vendor['gst_per'];

    if (this.tax != 'Country Tax') {
      this.calculation(value, index1);
    } else {
      this.calculateTax(1);
    }
  }

  calculation(value, position) {
    console.log(value);
    //for (let i = 0; i < this.materials.length; i++) {
    let material = this.materials[position];
    material['gross_total'] = +material['order_qty'] * +material['quotation_amt'];
    material['gst_total'] = +material['gross_total'] * (+material['gst_per'] * 1 / 100);
    material['net_total'] = +material['gross_total'] + +material['gst_total'] * 1;
    //}
  }

 

  // addQuotation() {
  //   window.open('#/purchase/quotation/new');
  //}
  calculateTax(value) {
    if (Number(this.tax_percent) < 0 || Number(this.tax_percent) > 100) {
      this.tax_percent = 0;
    }
    if (value == 0) {
      this.tax_percent = 0;
    }
    console.log(this.tax_percent);
    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      if (this.quotation_type != 'Local Purchase' && this.tax == 'Country Tax') {
        material['gst'] = this.tax_percent;
      } else {
        material['gst'] = material['gst_per'];
      }
      material['gross_total'] = +material['order_qty'] * +material['quotation_amt'];

      if (this.quotation_type != 'Local Purchase' && this.tax == 'Country Tax') {
        material['gst_total'] = +material['gross_total'] * (+this.tax_percent * 1 / 100);
      } else {
        material['gst_total'] = +material['gross_total'] * (+material['gst_per'] * 1 / 100);
      }

      material['net_total'] = +material['gross_total'] + +material['gst_total'] * 1;
    }
  }


  materials_data;

  addNewQuataion(material) {
    this.selectedMaterial = material;
    this.materials_data = material;
    this.isAddQuotation = true;
    this.vendor_map_data();
    console.log( this.materials_data);
  }

  vendors_map_data ={};

  vendor_map_data() {
    this.vendors_map_data =[];
    this.service.get('common.php?type=getvendormapdata&material_code='+this.selectedMaterial['material_code']).subscribe(response => {
      this.vendors_map_data = response;
    });
  }


  setGst(index) {
    this.gstObj = this.gsts[index];
  }






  getmaterialbyvendor(value,index) {
    this.materials_data =[];
    this.service.get('purchase/quotation.php?type=getvendorquatationmaterialforshort&vendor_no='+value+'&material_code='+this.selectedMaterial['material_code']).subscribe(response => {
      this.materials_data = response;
    });

    index=index-1;
    this.vendor_id = this.vendors_map_data[index]?.id;
  }




 

  getVendors() {
    this.vendors = [];
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }


  selectedFile:File;
  vendor_quotation_no;

  vendor_id;

  
  saveQuataion() {
    const selectedItems = this.materials_data.filter((term) => term.selected);
    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }
    console.log(selectedItems);
    const uploadData = new FormData();

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }

    uploadData.append("vendor_quotation_no",this.vendor_quotation_no);
    uploadData.append('material_type', 'NA');
    uploadData.append('vendor_no', this.vendor_id);
    uploadData.append('materials', JSON.stringify(selectedItems));
 
    
    this.service.post('purchase/quotation.php?type=saveQuotation', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Quotation saved successfully');
        this.isAddQuotation =false;
       } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 


 


    
}
