import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  results;
  material_subtype = '';
  material_nature = '';
  grade = '';
  id = '';
  selectedResult = [];
  manufactured_for = '';
  isEdit = false;
  grades;
  material_code = '';
  material_name = '';
  plants;
  materials = [];
  density = '';
  order_qty = '';
  inventory = '';
  inv_unit = '';
  location = '';
  clients;
  plant_id = '';
  is_corporate = '';
  plant_code = '';
  departments: Object;
  servicesList: any;
  isView = false;

  types = [
    'Annual Maintenance',
    'Equipment Service',
    'Breakdown Maintenance',
    'Manpower Service',
    'Training Service',
    'Consultancy Service',
    'Development Service',
    'Transport Service',
    'Contracts Service',
    'Other Service',
  ];

  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.service.observableGrade.subscribe((response) => {
      this.grades = response;
      this.service.get('common.php?type=getClients').subscribe((response) => {
        this.clients = response;
      });
    });
    this.getPlant();
    this.getDepartment();
    this.getServices();
    this.get_rights();
  }

  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
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
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//

  getServices() {
    this.service
      .get('master/service.php?type=getService')
      .subscribe((response) => {
        this.servicesList = response;
      });
  }

  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }

  // getMaterialType(){
  //   this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
  //     this.types= response;
  //   });
  // }
  // getMaterialsLog() {
  //   if (this.is_corporate == "1") {
  //     this.service.get('master/material.php?type=getMaterials&material_type=Raw Material' +'&plant_id=0').subscribe((response: any) => {
  //       this.results = response;
  //       this.filterMaterial();
  //     });
  //   } else {
  //     this.service.get('master/material.php?type=getMaterials&plant_id='+this.plant_id +'&material_type=Raw Material').subscribe((response: any) => {
  //       this.results = response;
  //       this.filterMaterial();
  //     });
  //   }
  // }
  getPlant() {
    this.service
      .get('master/plant.php?type=getPlant&show_corporate=0')
      .subscribe((response) => {
        this.plants = response;
      });
  }
  getClients(value) {
    if (value == 'Client') {
      this.service.get('common.php?type=getClients').subscribe((response) => {
        this.clients = response;
      });
    } else {
      this.manufactured_for = '';
    }
  }

  // filterMaterial() {
  //   this.materials = [];
  //   for (let i = 0; i < this.results.length; i++) {
  //     let material = this.results[i];
  //     if (material['material_subtype'].toUpperCase().includes(this.material_subtype.toUpperCase()) && material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())&& material['material_nature'].toUpperCase().includes(this.material_nature.toUpperCase()) && material['material_code'].toUpperCase().includes(this.material_code.toUpperCase())&& material['plant_id'].toUpperCase().includes(this.plant_code.toUpperCase())) {
  //       this.materials[this.materials.length] = material;
  //     }
  //   }
  // }

  downloadReport() {
    this.service.open(
      'qa/material.php?type=materialmasterlogrd&material_type=Raw Material&material_subtype=' +
        this.material_subtype +
        '&grade=' +
        this.grade +
        '&material_nature=' +
        this.material_nature
    );
  }
  download() {
    this.service.open(
      'qa/material.php?type=servicedetails&material_type=Raw Material&material_subtype=' +
        this.material_subtype +
        '&grade=' +
        this.grade +
        '&material_nature=' +
        this.material_nature
    );
  }

  view(index) {
    this.selectedResult = this.servicesList[index];
    this.isView = true;
  }
  edit(index) {
    this.selectedResult = this.servicesList[index];
    this.isEdit = true;
  }
  isEdit1 = false;

  editType(val) {
    if (val == 'ADD NEW') {
      this.isEdit1 = true;
    }
  }
  service_type;
  newType(val) {
    // console.log(val.value.typeField);
    this.types.push(val.value.typeField);
    this.service_type = val.value.typeField;
    this.isEdit1 = false;
  }

  editService(data, id) {
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    this.service
      .post(
        'master/service.php?type=updateService&id+' + id,
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        this.getServices();
        if (response['status'] == 'success') {
          alertify.success('Material Updates Successuly');
          this.isEdit = false;
        } else {
          alertify.error(response['status']);
        }
      });
  }

  delete(id, status) {
    this.service
      .get(
        'master/service.php?type=deleteService&id=' + id + '&status=' + status
      )
      .subscribe((response) => {
        this.getServices();
        if (response['status']) {
          alertify.success('Material Deleted Successuly');
        } else {
          alertify.error('some error occured');
        }
      });
  }

  AllRecord() {
    this.materials = this.results;
    this.material_subtype = '';
    this.material_nature = '';
    // this.grade = '';
    this.material_code = '';
    this.material_name = '';
    this.plant_code = '';
  }

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.servicesList; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.servicesList.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
}
