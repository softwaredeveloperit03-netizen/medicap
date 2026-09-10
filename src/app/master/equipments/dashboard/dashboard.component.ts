import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
 

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

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
  isEdit1 = false;
  isEdit = false;
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
    this.loggedInDept = localStorage.getItem('department');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.maxDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.minDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }


  ngOnInit(): void {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
    this.getDepartments();
    this.getEquipmentsLog();
     this.getStorePersons();
     this.get_rights();
     this.getequipment_type();
  }
 
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
      });
  }
 
 
 
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getEquipmentsLog() {
    this.service.get('master/equipment.php?type=getEquipments').subscribe(response => {
      this.results = response;
    });
  }

   

  equipment_type_data;
  getequipment_type() {
    this.service.get('master/equipment.php?type=getequipment_type_data').subscribe(response => {
      this.equipment_type_data = response;
    })
  }

 

  employees;
  getStorePersons() {
    this.service.get('employee.php?type=getEmployees').subscribe(response => {
      this.employees = response;
    })
  }


  download() {
    this.service.open('master/equipment.php?type=downloadEquipmentsLog&plant_name=' + this.plant_name + '&equipment_name=' + this.equipment_name + '&department_name=' + this.department_name)
  }


  isHistory = false;

  view(data) {
    this.selectedResult = data
    this.isView = true;
  }

  statusChange(data) {
    this.selectedResult = data
    this.isEdit1 = true;
  }

  statusChangedHis(data) {
    this.selectedResult = data
    this.isHistory = true;
  }

  change_status(data){


    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    this.service.post('master/equipment.php?type=changeEquipmentStatus&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isEdit1 = false;
         alertify.success('Status Change Successfully');
        data.resetForm();
        this.getEquipmentsLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }



  editEquipment(data) {
    this.selectedResult = {...data}
    this.id = this.selectedResult['id'];
    this.selected_location = this.selectedResult['location'];
    const index = this.departments.findIndex(dept => dept.department_name === this.selectedResult['department']);
    this.getSectionsByDept(index+1);
    this.isEdit = true;
  }

 



  editEquipmentdata(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    
    this.service.post('master/equipment.php?type=editEquipment&id='+this.id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isEdit = false;
        this.getEquipmentsLog();
        alertify.success('Record Update Successfully');
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  

  getSectionsByDept(index) {
    this.sections = [];
    this.sections = this.departments[index - 1]['sections'];
  }




  AllRecord() {
    this.materials = this.results;
    this.department_name = '';
    this.equipment_name = '';
  }

 
   

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter(material => {
      // Check if the equipment_name field contains the search query
      return material.equipment_name && material.equipment_name.toLowerCase().includes(query);
    });
  }

 

  private baseUrl = 'https://mywebsite.com/details?id=';

  qrModalOpen = false;
  qrUrl: string = '';
  qrId: string | number = '';

  openQrModal(id: string | number) {
    this.qrId = id;
    const fullUrl = this.baseUrl + id;
    this.qrUrl = `https://quickchart.io/qr?text=${encodeURIComponent(fullUrl)}&size=300`;
    this.qrModalOpen = true;
  }

  downloadQr() {
    if (!this.qrUrl || !this.qrId) return;

    const link = document.createElement('a');
    link.href = this.qrUrl;
    link.download = `qr-${this.qrId}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }




}

 