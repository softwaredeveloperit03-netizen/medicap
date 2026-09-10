import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-location-chart',
  templateUrl: './location-chart.component.html',
  styleUrls: ['./location-chart.component.css']
})
export class LocationChartComponent implements OnInit {

  results;
  sections;
  sectionsrack;
  section_code = '';
  material_type = '';
  isView=false;
  racks;
  selectedResult=[];
  rackList=[];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getAllocatedRacks();
    this.getSections();
    this.getSectionsRacks();
  }

  getAllocatedRacks() {
    this.service.get('store/location.php?type=getAllocatedRacks&section_code=' + this.section_code + '&material_type=' + this.material_type).subscribe(response => {
      this.results = response;
      console.log('re', this.results);
    });
  }
  allocate(index){
    this.selectedResult = this.results[index];
    console.log('test',this.selectedResult);
    this.isView = true;
  }

  getSections() {
    this.service.get('store/location.php?type=getSections').subscribe(response => {
      this.sections = response;
    });
  }

  getSectionsRacks() {
    this.service.get('store/location.php?type=getSectionRacks').subscribe(response => {
      this.sectionsrack = response;
    });
  }

  
  getRacks(index) {
    index = index - 1;
    if (index !== -1) {
      this.racks = this.sectionsrack[index].racks;
    } else {
      this.racks = [];
    }
  }

  addracks(data){
    let temp=data.value;
    this.rackList[this.rackList.length]=temp;
    temp['material_type']=this.selectedResult['material_type'];
    temp['material_code']=this.selectedResult['material_code'];
    // temp['section_name']=this.sectionsrack['section_name'];
    temp['qty']=this.selectedResult['qty'];
    data.reset();
  }

  delete(index){
    this.rackList.splice(index,1);
  }

  download(){
    this.service.open('store/location.php?type=downloadLocationChart&section_code=' + this.section_code + '&material_type=' + this.material_type)
  }

  saveRack(data){
    let temp=data.value;
    temp['prev_rack_no']=this.selectedResult['rack_no'];
    temp['qty']=this.selectedResult['qty'];
    temp['unit']=this.selectedResult['unit'];
    temp['material_code']=this.selectedResult['material_code'];
    this.service.post('store/location.php?type=shiftLocation',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Rack Asssing Successfuly');
        this.getAllocatedRacks();
        this.isView=false;
        data.resetForm();
      }else{
        alertify.error('some error occured');
      }
    });
  }
  
}