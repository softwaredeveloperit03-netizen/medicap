import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  dosage;
  product;
  selectedData=[];
  pracautionList=[];
  equipment;
  equipmentList=[];
  selectedOperator=[];
  isShow=false;
  machineList=[];
  deblisterList=[];
  dosage_form='';
  product_code='';
  overprintingList=[];
  isBlister=false;
  isBottel=false;
  isStrip=false;
  isYes=false;
  isSecYes=false;
  isTerYes=false;

  line_clearance = '';
  clearances = [];
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getDosages();
    this.getEquipments();
  }

  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.dosage=response;
    });
  }
  getProductsByDosage(value){
    this.service.get('common.php?type=getProductsByDosage&dosage_form='+value).subscribe(response=>{
      this.product=response;    
    });
  }


  Tertiaryselected(event){
    if(event.target.checked){
      this.isTerYes=true;
    }else {
      this.isTerYes=false;
    }
  }

  getProductDetails(index){
    index = index - 1;
    if(index !== -1){
      this.selectedData=this.product[index];
      console.log('dd',this.selectedData); 
    }
  }

  packingselected(event){
    if(event.target.checked){
      this.isYes=true;
    }else{
      this.isYes=false;
    }
  }
  secondaryselected(event){
    if(event.target.checked){
      this.isSecYes=true;
    }else {
      this.isSecYes=false;
    }
  }

  getSubtype(data){
    if(data=='Blister'){
      this.isBlister=true;
      this.isBottel=false;
      this.isStrip=false;
    }else if(data=='Bottle'){
      this.isBlister=false;
      this.isBottel=true;
      this.isStrip=false;
    }else if(data=='Strip'){
      this.isStrip=true;
      this.isBlister=false;
      this.isBottel=false;
    }
  }

  add(data){
    this.pracautionList[this.pracautionList.length]=data.value;
    data.reset();
  }
  deletelist(index){
    this.pracautionList.splice(index,1);
  }

  getEquipments(){
    this.service.get('common.php?type=getEquipments').subscribe(response=>{
      this.equipment=response;
    });
  }

  getequips(index){
    index = index  - 1;
    if(index !== -1){
      this.selectedOperator = this.equipment[index];
    }
  }

  equipmentAdd(data){
    if (data.valid) {
      this.equipmentList[this.equipmentList.length] = this.selectedOperator;
      data.reset();
    }
  }
  
  deleteEquip(index){
    this.equipmentList.splice(index,1);
  }

  onOverprintingChange(e) { 
    if(e.target.checked){
      this.isShow=true;
    }else{
      this.isShow=false;
    }
  }



  addmachine(data){
    this.machineList[this.machineList.length]=data.value;
    data.reset();
  }
  deleteMachinelist(index){
    this.machineList.splice(index,1);
  }

  addDeblist(data){
    this.deblisterList[this.deblisterList.length]=data.value;
    data.reset();
  }
  deleteDeblist(index){
    this.deblisterList.splice(index,1);
  }


  saveData(data,data1){
    // if(!data.valid){
    //   alert('All fields are required!');
    //   return;
    // }
    let temp=data.value;
    temp['product_code']=this.product_code;
    temp['dosage_form']=this.dosage_form;
    temp['precautions']=this.pracautionList;
    temp['equipments']=this.equipmentList;
    temp['overprinting']=data1.value;
    temp['process']=[{"step1":"Receipt Of Packing Material"},
    {"step2":"Line Clerance"},
    {"step3":"Machine checks and Setting","machinelist":this.machineList},
    {"step4":"First Pack Inspection Details","inspe_details":{"list1":"blister","list2":"blister per corton","list3":"Outer Shipper Qty","list4":"Configuration"}},
    {"step5":"Leak Test","list":[{"list1":"Date","list2":"Time","list3":"Operator","list4":"Humidity % Area","list5":"Ferming Roller temp","list6":"Sealing Roller Temp",
    "list7":"No of Blister Leaked","list8":"% Reg","list9":"Checked By"}]},
    {"step6":"On Line Inspection","onlineInspection":[{"list1":"Date","list2":"Time","list3":"Overprinting Embossing Quality","list4":"Tablet/Capsule Position",
    "list5":"Foil Allignment","list6":"Cutting","list7":"Missing Tablet","list8":"No.Of Shipping Packed","list9":"Checked By"}]},
    {"step7":"Secondary Packing","secondary_packing":[{"list1":"Date","list2":"Time","list3":"Blister checked ok/not ok","list4":"mono filled carton Inspected",
    "list5": "Filled carton check","list6":"OverPrinting Details check","list7":"Shipper count","list8":"Done By","list9":"Checked By"}]},
     {"step8":"Deblistering Record","deblisterrecord": [{"list1":"Machine Clerarance Stage","list2":"Line Clearance","list3":"Machine Setting","list4":"Blister Quantity Rejected (kg)",
     "list5":"Deblistering Process"}],"deblisterlist": this.deblisterList, Debl_output:[{"list1":"Date","list2":"Time","list3":"to","list4":"Rejected foil/entry blister weight kg","list5":"Checked By","list6":"Quantity Of Tablet Kg","list7":"gross wt","list8":"tare wt","list9":"net wt"}]},
     {"step9":"Reconcillation Of Packing Material","reconsillationList":[{"list1":"Details","list2":"Unit","list3":"Cartons","list4":"Mono cartons","list5":"Shipper","list6":"Shrink"}]},
     {"step10":"Foil Reconcillation Record"},
     {"step11":"Product Reconcillation"},
     {"step12":"Product Transfer To Finished Stores(FG Stores)"},
     {"step13":"Deviation Any"}
  ];
    temp['clearances'] = this.clearances;
    temp['machinechecks'] = this.machineList;


    this.service.post('packing/ebpr.php?type=saveBPR',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alert('data save successffuly');
        this.router.navigate(['/packing/ebpr']);
      }else{
        alert('some error occured');
      }
    });
  }

  addLineClearance() {
    if (this.line_clearance !== '') {
      let temp = {};
      temp['checkpoint'] = this.line_clearance;
      this.clearances[this.clearances.length] = temp;
      this.line_clearance = '';
    }
  }

}
