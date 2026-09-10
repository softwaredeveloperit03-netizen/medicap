import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-qualification',
  templateUrl: './qualification.component.html',
  styleUrls: ['./qualification.component.css'],
  providers: [DatePipe]

})
export class QualificationComponent implements OnInit {

  companyUnits;
  isView = false;
  results;
  equipment_name = '';
  equipment_type = '';
  status = '';
  selectedResult = [];
  departments;
  department_name = '';
  plant_name = '';
  equipments;
   units;
  sections;
  equipment_code;
  id;
  selected_location = '';
  minDate = '';
  maxDate = '';
  today = '';
  materials = [];
  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.maxDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.minDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }


  ngOnInit(): void {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
 
    this.getEquipmentsLog();
   }
 

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
     this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  getEquipmentsLog() {
    this.service.get('master/equipment.php?type=get_eqp_for_qualification' + '&equipment_name=' + this.equipment_name + '&department_name=' + this.department_name).subscribe(response => {
      this.results = response;
      console.log(this.results);
      this.filterMaterial();
    });
  }

  serch_eqp(value){

    this.service.get('master/equipment.php?type=searcheqp&value='+value).subscribe(response => {
      this.results = response;
      this.filterMaterial();
     });

  }




  download() {
    this.service.open('master/equipment.php?type=downloadEquipmentsLog&plant_name=' + this.plant_name + '&equipment_name=' + this.equipment_name + '&department_name=' + this.department_name)
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  

  getSections(index) {
    index = index - 1;
    if (index !== -1) {
      let temp = this.departments[index];
      this.sections = temp['sections'];
    }
  }
 

  

  getSectionsByDept(index) {
    this.sections = [];
    this.sections = this.departments[index - 1]['sections'];
   
  }


  qualificat =  false;

  qulificatn(){


    this.qualificat = true;

  }


  selectedFile:File;

  onFileChanged(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile = event.target.files[0];
    }
  }

  quali_report = '';

  addReport(data){

    const uploadData = new FormData();

    uploadData.append('equipment_code', this.selectedResult['id']);
    uploadData.append('equipment_name', this.selectedResult['equipment_name'] );

    if (this.selectedFile !== undefined) {
      uploadData.append('report', this.selectedFile, this.selectedFile.name);
    }
 
    this.service.post('qa/qualification.php?type=save_report&equipment_code='+this.selectedResult['equipment_code']+'&equipment_name='+this.selectedResult['equipment_name'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Report saved successfully');
        data.resetForm();
       } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }



  saveEquipment(data){


    let temp = data.value;

     this.service.post('qa/qualification.php?type=saveProduct', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Report saved successfully');
        data.resetForm();
        this.qualificat = false;
        this.qualificat = false;

       } else {
        alert('Failed: An error occured, please try again!');
      }
    });

  }


  AllRecord() {
    this.materials = this.results;
    this.department_name = '';
    this.equipment_name = '';
  }

  filterMaterial() {
    this.materials = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['department'].toUpperCase().includes(this.department_name.toUpperCase()) && material['equipment_name'].toUpperCase().includes(this.equipment_name.toUpperCase())) {
        this.materials[this.materials.length] = material;
      }
    }
  }
 




}
