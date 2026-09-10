import { Component, OnInit } from '@angular/core';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {

  isData = false;
  isdeviation = false;
  isView = false;
  isUpincedent=false;
  results;
  isupdate = false;
  selectedReport = [];
  receiveDetails=[];
  devdetails=[];
  productdetails=[];
  insdetails=[];
  incidentProduct=[];
  materials;
  material_code='';
  container_type='';
  unit='';
  challan_qty='';
  received_qty='';
  container_subtype='';
  containers='';
  challan_date='';
  error;
  check=[];
   checkPointData1: any;
  checkPointData: any;

  
  labors;
  po_status = '';
  isViewCheck = false;
  isContainer = false;
 
  
  entry_time;

  isChecklist = false;
  isTanker = false;
  pack_size;
  selectedPO = [];
 
  labelList1 = [];
  equipments;
  selectedFile: File;
  isUpload = 0;
  isDamage = false;
  from_time;
  to_time;
  isDedYes = false;
  isDedNo = false;

  
  qty_received;
  total_containers;
  outer_damage = 0;
  inner_damage = 0;
  manufacturer = '';
  qtyReceived = 0;
  containerTotal = 0;
  units;
  tankers;
  outerDamage = 0;
  innerDamage = 0;
  qty_status = 0;
  result = 0;
  tankerchecklist;
  ismanual = false;
  isvaccume = false;
  minDate = '';
  clicked = false;
  maxDate = '';
  finalcontainr;
  challan_no = '';
  employees;
  types;
  max_time;

  from_date = '';
  to_date = '';
  po_number = '';
  storage_condition = 'NO';
  today = '';
  material_subtype = '';
  material_type='';
  checklist:any=[
   
  ];
  
  submitBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  validateBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessReceivings();
    this.getMaterials();
    // this.AllRecord();
     
    // this.getContainer();
    this.getTanker();
    this.getStorePersons();
    this.getMaterialType();
    this.getCurrentTime();
    this.getStoresupplier();
   
    this.getCheckPointData();
   
  }

 
  getInprocessReceivings() {
    this.service.get('store/raw.php?type=getRejectedChallans&material_code=' +this.material_code).subscribe(response => {
      this.results = response;
    });
  }
  // AllRecord() {
  //   this.material_code = '';
  //   this.service.get('store/raw.php?type=getAllInprocessReceivings').subscribe(response => {
  //     this.results = response;
  //   });
  // }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials=response;
    })
  }
  deviation(index)
  {
    this.selectedReport = this.results[index];
    this.isdeviation=true;
    this.isData=false;
  }
  edit(index){
    this.selectedReport = this.results[index];
    this.isData=true;
    this.submitBtnState = ClrLoadingState.LOADING;


  }
  errorUpdate(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('store/raw.php?type=update_checkReceivedMaterial1&challan_no='+this.selectedReport['challan_no'], JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.success('Material updated successfully');
        this.isData = false;
        this.getInprocessReceivings();
      } else {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  selcted_batches=[];
  view(index) {
    this.selectedReport = this.results[index];
    this.selcted_batches=this.selectedReport['batches']
    console.log( this.selcted_batches['id'])
    this.receiveDetails=this.selectedReport['receiving_details'];
    // this.container_type=this.receiveDetails['container_type'];
    // this.container_subtype=this.receiveDetails['container_subtype'];
    this.unit=this.selectedReport['unit'];
    this.challan_qty=this.selectedReport['challan_qty'];
    this.received_qty=this.selectedReport['received_qty'];
    this.containers=this.selectedReport['containers'];
    this.po_status=this.selectedReport['po_status']

  
    if(this.selectedReport['error_type']=='error2'){
      this.devdetails=this.selectedReport['deviations'];
      this.productdetails=this.devdetails['product_details'];
      console.log(this.productdetails);
    }else if(this.selectedReport['error_type']=='error1'){
      this.insdetails=this.selectedReport['incidents'];
     this.incidentProduct=this.insdetails['product_details'];
     console.log('tt',this.incidentProduct);
   }


   this.getChkListData(this.selectedReport['receiving_no']);
   
    this.isView = true;
    this.get_save_sampling_batch();



    
  }
  labelList;
get_save_sampling_batch() {
  this.qtyReceived=0;
  this.containerTotal=0;
  this.service.get('store/receive.php?type=get_save_sampling_batch&challan_no=' + this.selectedPO['challan_no']).subscribe(response => {
    this.labelList = response;

    for (let i = 0; i < this.labelList.length; i++) {
      this.qtyReceived += +this.labelList[i]['qty_received'];
      this.containerTotal += +this.labelList[i]['total_containers'];
      this.outerDamage += +this.labelList[i]['outer_damage'];
      this.innerDamage += +this.labelList[i]['inner_damage'];
    }

    let test = this.containerTotal;
    this.finalcontainr = Math.ceil(test);
    this.labelList['total_containers'] = this.finalcontainr;

    console.log(this.qtyReceived);
    console.log(this.containerTotal);
    console.log(this.outerDamage);
    console.log(this.innerDamage);

    if (this.selectedPO['qty'] > 0) {
      this.result = this.qtyReceived - this.selectedReport['qty'];
    } else {
      this.result = this.qtyReceived - this.selectedReport['challan_qty'];
    }

    if (this.result >= 0) {
      this.po_stat = 'Extra';
      console.log('status=' + this.po_stat);
    } else {
      this.po_stat = 'Short';
      console.log('status=' + this.po_stat);
    }
  });
}
po_stat;

  viewCoafile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    url = this.service.url + 'upload/challan/' + url;
    window.open(url, '_blank');
    // window.open(this.service.url+ 'upload/challan/' + this.selectedReport['challan_file']);

  }

  update() {
  
    this.selectedReport['checklist']=this.checklist;
    this.submitBtnState = ClrLoadingState.LOADING;
    this.service.post('store/raw.php?type=checkReceivedMaterial1&b_id='+this.selcted_batches['id']+'&cm_id='+this.selectedReport['cm_id'], JSON.stringify(this.selectedReport)).subscribe(response => {
      if (response['status'] == 'success') {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.success('Material updated successfully');
        this.isView = false;
        this.getInprocessReceivings();
      } else {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  // update(status) {
  //   this.submitBtnState = ClrLoadingState.LOADING;
  //   this.service.get('store/raw.php?type=checkReceivedMaterial1&status=' + status + '&id=' + this.selectedReport['id']+ '&challan_id=' + this.selectedReport['challan_id']).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       this.submitBtnState = ClrLoadingState.DEFAULT;
  //       alertify.success('Material updated successfully');
  //       this.isView = false;
  //       this.getInprocessReceivings();
  //     } else {
  //       this.submitBtnState = ClrLoadingState.DEFAULT;
  //       alertify.error('Failed: An error occured, please try again!');
  //     }
  //   });
  // }

  getChkListData(rec_no) {
    this.service.get('master/checklist.php?type=get_rec_ChkListByTranID&rec_no='+rec_no).subscribe(response => {
      this.checklist = response;
    });
  }
  vendorM;
  getManufactures() {
    this.service.get('common.php?type=getRAWManufactures&material_type='+this.selectedPO['material_type']).subscribe(response => {
      this.vendorM = response;
    });
  }
  
   
  batches=[];


  
  getTankerchklist() {
    this.service.get('store/raw.php?type=getTankerchklist&form=Receiving(Tanker)&trans_id='+this.selectedPO['challan_no']).subscribe(response => {
      this.tankerchecklist = response;
    });
  }

   
  

  AllRecord() {
    this.service.get('store/raw.php?type=getAllPendingReceivings').subscribe(response => {
      this.results = response;
    });
    this.po_number = '';
    this.material_subtype = '';
    this.from_date = '';
    this.to_date = '';
  }

  getMaterialType() {
    this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
      this.types = response;
    });
  }
  getCurrentTime( ) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    this.entry_time = h + ':' + m;
    this.max_time = h + ':' + m;
  }


  getTanker() {
    this.service.get('store/raw.php?type=getCheckPointByForm&module=Receiving&form=Receiving(Tanker)').subscribe(response => {
      this.tankers = response; 
    })
  }

  getStorePersons() {
    this.service.get('employee.php?type=getStorePersons').subscribe(response => {
      this.employees = response;
    })
  }
  suplier;
  getStoresupplier() {
    this.service.get('purchase/vendor.php?type=getStoresupplier').subscribe(response => {
      this.suplier = response;
      // console.log('rd',this.suplier);
    })
  }


  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  viewResult(index) {
    this.selectedPO = this.results[index];
   
    if (this.selectedPO['is_tanker'] == 'YES') {
      this.container_type = 'Tanker';
    }
    this.isView = true;
    this.getManufactures();
  }

  checkdamage(value) {
    if (value == 'Yes') {
      this.isDamage = true;
    } else {
      this.isDamage = false;
    }
  }

  close() {
    this.isView = false;
    this.container_type = 'Bag';
  }

  onFileChanged(event) {
    if (event.target.files == 0) {
      this.isUpload = 0;
    } else {
      this.selectedFile = event.target.files[0];
      this.isUpload = 1;
    }
  }

  getCleaner(show) {
    if (show == 'Manual') {
      this.ismanual = true;
      this.isvaccume = false;
    } else if (show == 'Vaccume Cleaner') {
      this.ismanual = false;
      this.isvaccume = true;
    }
  }

  addlabel(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
   // console.log(temp);

    // let qty = +this.qtyReceived + +temp['qty_received'];
    // if (+qty > +this.challan_qty) {
    //   alertify.error('Challan Qty & Received Qty not matching!');
    //   return;
    // }
    temp['unit'] = this.selectedPO['unit'];
    this.labelList[this.labelList.length] = temp;
    console.log(this.labelList);
    this.qtyReceived += +temp['qty_received'];
    this.containerTotal += +temp['total_containers'];
    this.outerDamage += +temp['outer_damage'];
    this.innerDamage += +temp['inner_damage'];
    let test = this.total_containers;
    this.finalcontainr = Math.ceil(test);
    temp['total_containers'] = this.finalcontainr;

    data.resetForm();
    if (this.selectedPO['qty'] > 0) {
      this.result = this.qtyReceived - this.selectedPO['qty'];
    } else {
      this.result = this.qtyReceived - this.selectedPO['challan_qty'];
    }
  }
 

  getdedusting(show) {
    if (show == 'Yes') {
      this.isDedYes = true;
      this.isDedNo = false;
    } else if (show == 'No') {
      this.isDedYes = false;
      this.isDedNo = true;
    }
  }

  deleteLabel(batch_no) {
    this.service.post('store/receive.php?type=delete_save_sampling_batch&batch_no=' + batch_no, JSON.stringify(batch_no)).subscribe(response => {
      if(response['status']=='success') {
        alertify.success(response['msg']);
        this.get_save_sampling_batch(); 
      } else{
        alertify.error(response['msg']);
      }
    });
   
  }
  deleteLabel1(index) {
    this.qtyReceived = this.qtyReceived - this.labelList[index].qty_received;
    this.labelList.splice(index, 1);
    console.log(this.qtyReceived);
  }

  receiveMaterial(data, data1) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.po_status) {

    }
    let temp = data.value;
    // temp['checklist'] = this.checkPointData;
    // temp['checklist1'] = this.checkPointData1;
    // let temp2 = data1.value;
    // if (+temp2["challan_qty"] !== +temp["received_qty"]) {
    //   alertify.error('Challan Qty & Received Qty not matching!');
    //   return;
    // }

    const uploadData = new FormData();
    if (this.isUpload === 1) {
      uploadData.append('coa', this.selectedFile, this.selectedFile.name);
    } else {
      if (temp['coa_received'] == 'Yes') {
        alertify.error('COA file is Compulsory');
        return;
      }
    }
    Object.keys(temp).forEach(key => {
      let value = temp[key];
      if (key == 'dedusting') {
        uploadData.append(key, JSON.stringify(value));
      } else if (key == 'deviation') {
        uploadData.append(key, JSON.stringify(value));
      } else {
        uploadData.append(key, value);
      }
    });

    let temp1 = data1.value;
    Object.keys(temp1).forEach(key => {
      let value = temp1[key];
      uploadData.append(key, value);
    });

    uploadData.append("material_code", this.selectedPO['material_code']);
    uploadData.append('batches', JSON.stringify(this.labelList));
    uploadData.append("challan_no", this.selectedPO['challan_no']);
    uploadData.append("checklist", JSON.stringify(this.checkPointData));
    //temp['checklist'] = this.checkPointData;
     let temp3 = this.selectedPO;
     temp3['checklist'] = this.checklist;
    temp3['checkPointData'] = this.checkPointData;

    console.log(this.checkPointData);


    this.service.post('store/raw.php?type=receiveMaterial&id=' + this.selectedPO['id']+ '&challan_id=' + this.selectedPO['challan_id'], uploadData).subscribe(response => {
        if (response['status'] === 'success') {
        alertify.success('Material Received Successfully');
        data.resetForm();
        this.labelList = [];
       
        this.isView = false;
        data.resetForm();
        data1.resetForm();
         
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

  number(value) {
    if (isNaN(value)) {
      alertify.error('Number Only');
      return false;
    }
  }


  addContainer() {
    if (this.container_type == 'Add New') {
      this.isContainer = true;
    } else {
      this.isContainer = false;
    }
  }
  saveContainer(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('master/container.php?type=saveContainers', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
         this.isContainer = false;
        alert('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }
  

  getContainerNo() {
    this.total_containers = parseInt((this.qty_received * 1 / this.pack_size * 1).toFixed(2));
  }
  getCheckPointData(){
   
    this.service.get('store/raw.php?type=getCheckPointByForm&module=Receiving&form=Receiving').subscribe(response => {
     this.checkPointData = response;
    
   });

 }
//   getCheckPointData1(){
   
//     this.service.get('master/checklist.php?type=getCheckPointByForm&module=Receiving&form=Damage').subscribe(response => {
//      this.checkPointData1 = response;
    
//    });

//  }

//  save(data2){
//   console.log(data2.value);
//   let temp = data2.value;
//   temp['checklist'] = this.checkPointData;
//   // temp['checklist1'] = this.checkPointData1;
//   this.service.post('store/raw.php?type=savechlist', JSON.stringify(temp)).subscribe(response => {
//     if (response['status'] == 'success') {
//       alertify.success(' Updated successfully');
//       this.isView = true;

//     } else {
//       alertify.error('Failed: An error occured, please try again!');
//     }
//   });

//  }





  


  fromlevel1=[];
   result1=[];
   
   
   
   addlabel1(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.fromlevel1=[];
      let temp1 = data.value;
    
 
 this.fromlevel1[this.fromlevel1.length] =temp1;
 
      console.log(this.fromlevel1);
  }

}
