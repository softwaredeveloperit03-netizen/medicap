import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-spplanapprove',
  templateUrl: './spplanapprove.component.html',
  styleUrls: ['./spplanapprove.component.css']
})
export class SpplanapproveComponent implements OnInit {

  results;
  std_actual_yeilds: number = 0;
  actual_yeilds: number = 0;
  isView = false;
  materials = [];
  selectedResult;
  work_order_lots=[];
  pack_sizes=[];
  work_order_raw_materials = [];
  work_order_packing_materials = [];
  plant_id;
  packing_material: any;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getPlans();
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }
  selectedpacks=[];
  lot_b_size;
  balance_qty=0;
  get_pmData(index){
this.selectedpacks=this.pack_sizes[index-1];
this.lot_b_size = (this.batch_size / this.selectedResult['no_of_lots']).toFixed(6);
this.balance_qty=this.lot_b_size;
console.log(this.lot_b_size);
  }
  getPlans() {
    this.pack_sizes=[];
    this.service.get('store/dispensing.php?type=get_pm_workorders_for_approval_sp').subscribe(response => {
      this.results = response;
    
    });
  }
  // actual_yeilds;
  batch_size;
  pack;
  // batch_size;
  x;
  y;
  z;
  // actual_yeilds;
  yield(){
    // this.x=this.selectedbatch['total_qty'];
    this.y=this.actual_yeilds;
    this.z=this.batch_size;
    console.log('total_qty');
    console.log(this.x);
    console.log('actual_yeilds');
    console.log(this.y);
    console.log('batch_size');
    console.log(this.z);
    
  }
  no_of_packs;
  packing_qty;
  no_packs(){



    let packing = 0;

    packing = this.packing_qty * 1000;

    console.log(packing);


    this.no_of_packs= Math.round(packing/this.pack_size); 


    console.log(this.no_of_packs);


  }

  generateData() { 

    

    for (let j = 0; j < this.packing_material.length; j++) {
      if(this.pack_size_unit=='Kg'||this.pack_size_unit=='kg'){
        // let pcks=this.pack_size/1000;
        this.packing_material[j]['actual_qtyy'] = (this.actual_yeilds / this.pack_size) * this.packing_material[j]['total_qty'];
        this.packing_material[j]['pack_size']=this.pack_size;
        this.packing_material[j]['pack_size_unit']=this.pack_size_unit;
        console.log(this.packing_material[j]['actual_qtyy']);
      }
      else if(this.pack_size_unit=='gm'|| this.pack_size_unit=='g'){
         let pcks=this.pack_size/1000;
         this.packing_material[j]['actual_qtyy'] = (this.actual_yeilds / pcks) * this.packing_material[j]['total_qty'];
       
         console.log(this.packing_material[j]['actual_qtyy']);
      }
     
  }
      // console.log(this.pack['packing_material']);
      console.log('hi')
      }

      genrated_list=[];
      AddgenerateData(data){

        
       this.balance_qty -= this.packing_qty;


      //   if(this.balance_qty < 0 ){
      //     alertify.error('Check Balance qty');
        
      //   }else{       

        
        if (!data.valid ) {
          alertify.error('All fields are required');
          return;
        }
        let temp = data.value;
        temp['packing_list']=this.packing_material;
        this.genrated_list[this.genrated_list.length] = temp;
        console.log(this.genrated_list);
        this.std_actual_yeilds = this.std_actual_yeilds - this.actual_yeilds;
        this.actual_yeilds=0;
        this.pack_size='';
        this.pack_size_unit='';
        this.packing_qty=0;
        this.no_of_packs=0;
        
      // }

    }

      
      isShow=false;
  view(idx) {
    this.selectedResult = this.results[idx];
    
    this.pack_sizes = [];
    this.materials = this.results[idx]['materials'];
    this.pack_sizes = this.results[idx]['pack_sizes'];
    

    // this.isView = true;
    this.isShow = true;  
      this.get_int_sift();

  }
  int_sifters;
  get_int_sift() {
    this.service.get('production/product.php?type=get_savebmr_sift_pkplanning&id='+this.selectedResult['id']).subscribe(response => {
      this.int_sifters = response;
    });
  }
  selected_sifter=[];
  add(index){
    this.selected_sifter=this.int_sifters[index]
    console.log(this.selected_sifter)
    this.isView = true;
    this.isShow = false;  
  }
  pm_results;
  pack_size;
  selectedpacksize;
  pack_size_unit;
  get_pm_dtl(index) {
    this.selectedpacksize=this.pack_sizes[index-1];
    this.pack_size_unit=this.selectedpacksize['pack_size_unit']
    this.service.get('store/dispensing.php?type=get_pm_workorders_for_approval_sppm_data&work_order_id=' + this.selectedResult['id'] + '&batch_plan_id=' + this.selectedResult['batch_plan_id']+'&pack_size='+this.pack_size).subscribe(response => {
      this.packing_material = response;
    });
    console.log(this.pack_size_unit);
    this.actual_yeilds = 0;
  }
  
  saveData(status){
    this.service.get('production/workorder.php?type=approve_work_order&id='+this.selectedResult['id']+'&status='+status).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Status has been saved successfully');        
        this.router.navigate(['/packing/planning-approval']);
      } else {
        alertify.error('Failed: '+response['status']);
      }
    });
  }packing_materials = [];
  saveData_saipro(status){
    this.packing_materials = [];
    for (var x = 0; x < this.pack_sizes?.length; x++) {
      for (var i = 0; i < this.pack_sizes[x]['packing_material'].length; i++) {

        this.packing_materials.push( this.pack_sizes[x]['packing_material'][i]);
      }
    }
    
    let dataObj = {

      "packing_materials": this.genrated_list,      

    }


    
    console.log("genrated_list");
    console.log(this.genrated_list);
    console.log("packing_materials");
    console.log(this.packing_materials);
   

    this.service.post('store/dispensing.php?type=approve_work_order_saipro_pm&id='+this.selectedResult['id']+'&status='+status+'&sift_id='+this.selected_sifter['id']+'&batch_size='+this.batch_size , JSON.stringify(dataObj)).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Status has been saved successfully');        
        // this.router.navigate(['/packing/spplanning-approval']);
        this.isView = false;
        this.getPlans();
        this.plant_id = this.service.getPlantConfigFields("plant_id")
      } else {
        alertify.error('Failed: '+response['status']);
      }
    });


  }

}
