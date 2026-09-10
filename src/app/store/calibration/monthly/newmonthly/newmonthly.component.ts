import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-newmonthly',
  templateUrl: './newmonthly.component.html',
  styleUrls: ['./newmonthly.component.css']
})
export class NewmonthlyComponent implements OnInit {

  constructor(private service:DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getSECTIONS();
    this.getUnit();
    this.full_range_calibration_weight();
  }
  sections;
  equipment_id;
  standard_weight2;
  units;
  full_weights;
  ids;
  delete;
  getUnit() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }
  getSECTIONS() {
    this.service.get('common.php?type=getSECTIONS&department1=Store').subscribe(response => {
      this.sections = response;
    });
  }
  full_range_calibration_weight() {
    this.service.get('qc/raw.php?type=getfull_range_calibration_weight').subscribe(response => {
      this.full_weights = response;
    });
  }
  del_data(id) {
    this.service.get('qc/raw.php?type=del_data&id='+id).subscribe(response => {
      this.delete = response;
      this.full_range_calibration_weight();
    });
  }

  getbal_id(value){
    this.service.get('common.php?type=getbal_id&department1=Store&location='+value).subscribe(response => {
      this.ids = response;
    }); 
  }
  selecteddata=[];
  range_from;
range_to;
  getSelected(index){
    this.selecteddata=this.full_weights[index-1];
    console.log(this.selecteddata)
    this.range_from=this.selecteddata['range_from']
    this.range_to=this.selecteddata['range_to']
  }
  selectedResult=[];
  make;
  max_capacity;
  model;
  equipment_sr_no;
  Least_Count;
  unit;
  get_data(index){
    this.selectedResult = this.ids[index-1];

    this.make = this.selectedResult['make'];
    this.max_capacity = this.selectedResult['capacity'];
    this.model = this.selectedResult['model'];
    this.equipment_sr_no = this.selectedResult['serial_no'];
    this.Least_Count	 = this.selectedResult['working_capacity'];
    this.unit	 = this.selectedResult['unit'];
  }


  isdiv: boolean;
  isdiv2: boolean;
  isreq1 = false;
  sysShow() {
    this.isdiv = !this.isdiv;
  }
  sysShow2() {
    this.isdiv2 = !this.isdiv2;
  }
  inputShow1() {
    this.isreq1 = !this.isreq1;
  }
  isContainer = false;
  addWeights() {   
      this.isContainer = true;  
}
weights_data=[];
addwts(data){
  let temp = data.value;
  temp['range_from']=this.selecteddata['range_from']
  temp['range_to']=this.selecteddata['range_to']
  this.weights_data[this.weights_data.length]=temp;
  data.resetForm();
  console.log(this.weights_data);
}

delwt(index) {
  this.weights_data.splice(index, 1);
}
Del_AddLoad(index) {
  this.weights_data.splice(index, 1);
}
Del_Displayed_weight(index) {
  this.displayedload_test_List.splice(index, 1);
}


addData(data) {
  console.log(data.value);
  if (!data.valid) {
  alert('All fields are required');
  return;
}
  let temp = data.value;
  
  
  this.service.post('qc/raw.php?type=save_full_range_wt', JSON.stringify(temp)).subscribe(response => 
  {
    if (response['status'] == 'success') {
      alert('Weight Saved Successfully');
      data.resetForm();
      this.full_range_calibration_weight();

    } else {
      console.log(response);
      alert('Failed: An error occured, please try again!');
    }
  }); 
}
load_test_List=[];



standard_deviation;

    AddLoad(data){
      this.standard_deviation = 0;


      let temp=data.value;
      this.load_test_List[this.load_test_List.length]=temp;
      data.resetForm();
      console.log(this.load_test_List);

      let len = Number(this.load_test_List.length);

      console.log("length "+len);

      let total = 0;
      let mean = 0;

        for(var i = 0; i< this.load_test_List.length;i++){
          total += Number(this.load_test_List[i].observed_weight);

          console.log("this.load_test_List[i].observed_weight"+this.load_test_List[i].observed_weight)
        }

        mean = Number( total / len);

        console.log("mean "+mean);

       let second_addition = 0;

        for(var i = 0; i< this.load_test_List.length;i++){
            let sqr = Number(this.load_test_List[i].observed_weight - mean);
            let sqrt =   sqr * sqr;
            second_addition +=     sqrt;        
        }

        console.log("second_addition"+second_addition);


        let third_calculation =  Number(second_addition / len);

       this.standard_deviation = Math.sqrt(third_calculation);


       for(var i = 0; i< this.load_test_List.length;i++){

        this.load_test_List[i].standard_deviation = this.standard_deviation;
        this.load_test_List[i].standard_weight2 = this.standard_weight2;
        
      }


    }
    load_test_List_data=[];
    load1=[];

   
      AddLoad_Forms() {
        // Create a copy of load_test_List and push it into load_test_List_data
        this.load_test_List_data.push([...this.load_test_List]);
      
        // Reset load_test_List to an empty array
        this.load_test_List = [];
      
        // Reset other variables if needed
        this.standard_weight2 = 0;
        this.standard_deviation = 0;
      
        console.log(this.load_test_List_data);
      }
      // AddLoad_Forms(){
      // this.load_test_List_data[this.load_test_List_data.length]=this.load_test_List;
      // this.load_test_List =[];
      // this.standard_weight2 = 0;
    //   // this.standard_deviation = 0;
    //   console.log(this.load_test_List_data);

    // }

    standard_deviation2;
    displayedload_test_List=[];
    uncertainity;
    AddDisplayLoad(data){
      this.standard_deviation2 = 0;


      let temp=data.value;
      temp['standard_weight2']=this.standard_weight2;
   
      this.displayedload_test_List[this.displayedload_test_List.length]=temp;
   
      data.resetForm();
      console.log(this.displayedload_test_List);

      let len2 = Number(this.displayedload_test_List.length);

      console.log("length "+len2);

      let total2 = 0;
      let mean2 = 0;

        for(var i = 0; i< this.displayedload_test_List.length;i++){
          total2 += Number(this.displayedload_test_List[i].Displayed_weight);

          console.log("this.displayedload_test_List[i].Displayed_weight"+this.displayedload_test_List[i].Displayed_weight)
        }

        mean2 = Number( total2 / len2);

        console.log("mean "+mean2);

       let second_addition2 = 0;

        for(var i = 0; i< this.displayedload_test_List.length;i++){
            let sqr = Number(this.displayedload_test_List[i].Displayed_weight - mean2);
            let sqrt =   sqr * sqr;
            second_addition2 +=     sqrt;        
        }

        console.log("second_addition"+second_addition2);


        let third_calculation2 =  Number(second_addition2 / len2);

       this.standard_deviation2 = Math.sqrt(third_calculation2);
       this.uncertainity=(this.standard_deviation2*2)/mean2;
       console.log(this.uncertainity);
       


       for(var i = 0; i< this.displayedload_test_List.length;i++){

        this.displayedload_test_List[i].standard_deviation = this.standard_deviation2;
        this.displayedload_test_List[i].uncertainity = this.uncertainity;
        
      }

    }

    location;
    save(data){
let temp=data.value;
temp['Full_Range_calibration']=this.weights_data;
temp['Corner_Load_Test']=this.load_test_List_data;
temp['Repetability_Test']=this.displayedload_test_List;
temp['uncertainity']=this.uncertainity;
temp['location']=this.location;

this.service.post('qc/calibration/bulkdensity.php?type=save_monthly', JSON.stringify(temp))
.subscribe(response => {
  if (response['status'] == 'success') {
    alert('Saved Successfully');
    data.reset();
  } else {
    alert('Failed: An error occurred, please try again!');
  }
});

    }



}
