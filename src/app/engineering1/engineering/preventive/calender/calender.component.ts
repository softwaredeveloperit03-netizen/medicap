import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-calender',
  templateUrl: './calender.component.html',
  styleUrls: ['./calender.component.css']
})
export class CalenderComponent implements OnInit {
  isView = false;
  isLast = false;
  equipmentslog;
 
  selectedResult = [];
 

  calibration_type='';
 


  constructor(private service:DataAccessService) { }

  ngOnInit() {
     this.getEquipmentsLog();
     //this.getDepartments();
  }



  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        //this.departments = response;
      });
  }




  




  getEquipmentsLog(){
    this.service.get('engineering/calibration.php?type=getEquipments').subscribe(response=>{
      this.equipmentslog=response;
    });
  }
 

  view(index){
    this.selectedResult=this.filteredMaterials[index];
     this.isView = true;
  }

  view1(index){
    this.selectedResult=this.filteredMaterials[index];
     this.isLast = true;
  }
  


  searchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.equipmentslog; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.equipmentslog.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
 
  departments = [
    { id: 1, department_name: 'Engineering ' },
    { id: 2, department_name: 'Quality Assurance'},
    { id: 3, department_name: 'Quality Control'},
    { id: 4, department_name: 'Production'},
    { id: 5, department_name: 'R AND D' },
    { id: 6, department_name: 'Store' },
 
  ];


  calibration_frequency_inhouse = [
    { id: 1, particular: 'Daily ', checked : false ,"last_cali_date" : '', "last_prevent_date" : '' },
    { id: 2, particular: 'Weekly', checked : false ,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 3, particular: 'FortNightly', checked : false ,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 4, particular: 'Monthly', checked : false ,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 5, particular: 'Quarterly' , checked : false,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 6, particular: 'Half-Yearly' , checked : false,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 7, particular: 'Annually', checked : false ,"last_cali_date" : '', "last_prevent_date" : ''},

  ];
  calibration_frequency_external = [
    { id: 1, particular: 'Daily', checked : false,"last_cali_date" : '', "last_prevent_date" : '' },
    { id: 2, particular: 'Weekly' , checked : false,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 3, particular: 'FortNightly', checked : false ,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 4, particular: 'Monthly' , checked : false,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 5, particular: 'Quarterly', checked : false ,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 6, particular: 'Half-Yearly' , checked : false,"last_cali_date" : '', "last_prevent_date" : ''},
    { id: 7, particular: 'Annually', checked : false,"last_cali_date" : '', "last_prevent_date" : '' },

  ];
  

  inhouse_department = 'Engineering';
  external_department = 'Engineering';


  updateEquipmentdata(data){


    let obj = {
      "id" : this.selectedResult['id'],
      "equipment_id":this.selectedResult['id'],
      "inhouse_department":this.inhouse_department,
      "external_department":this.external_department,
      "calibration_frequency_external" : this.calibration_frequency_external.filter(item => item.checked),
      "calibration_frequency_inhouse" : this.calibration_frequency_inhouse.filter(item => item.checked),
    };
    console.log(data.value);
    
    this.service.post('engineering/calibration.php?type=update_calibration_frequency', JSON.stringify(obj)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.isView = false;
        this.getEquipmentsLog();
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('Failed:'+result.status);
      }
    });


  }




  editEquipment() {
    let obj = {
      "id" : this.selectedResult['id'],
      "equipment_id":this.selectedResult['id'],
      "calibration_frequency_external" : this.selectedResult['calibration_frequency_external'],
      "calibration_frequency_inhouse" : this.selectedResult['calibration_frequency_inhouse'],
    };

    this.service.post('engineering/calibration.php?type=update_equipment_Calibration_schedule', JSON.stringify(obj)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.isLast = false;
        this.getEquipmentsLog();
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('Failed:' + result.status);
      }
    });
  }





}
