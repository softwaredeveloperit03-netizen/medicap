import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  affecting_product = 'yes';
  affecting_equipment = 'no';

  department;
  employees;
  vendors;
  departments;
  materials;
  isMaterial = false;
  isproduct = false;
  isEquipment = false;
  israw = false;
  ispacking = false;
  selectedMaterial = [];
  materialList = [];
  selectedProduct = [];
  products;
  productList = [];
  selectedEquipment = [];
  equipments;
  equipmentList = [];
  deviations = [];
  observed;
  department_name='';

 

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getDepartments();
    this.getEmployees();
    this.getEquipments();
    this.getProducts();
    this.getObserved();
  }

  // getDepartments() {
  //   this.service.get('common.php?type=getDepartments').subscribe(response => {
  //     this.departments = response;
  //   })
  // }


  getDepartments(){
    this.departments = [
      { department_name: 'Human Resource', value: false},
      { department_name: 'Quality Control', value: false},
      { department_name: 'Account', value: false},
      { department_name: 'Security', value: false},
      { department_name: 'Purchase', value: false},
      { department_name: 'Quality Assurance', value: false},
      { department_name: 'Engineering', value: false},
      { department_name: 'Store', value: false},
      { department_name: 'Packing', value: false},
      { department_name: 'R & D', value: false},
      { department_name: 'Marketing', value: false},
      { department_name: 'Vendor', value: false},
      { department_name: 'Management', value: false},
      { department_name: 'Planning', value: false},
      { department_name: 'Admin', value: false},
      { department_name: 'IPQA', value: false},
      { department_name: 'Regulatory', value: false},
      { department_name: 'EHS', value: false},
      { department_name: 'Enginering Store', value: false},
      { department_name: 'Production general', value: false},
      { department_name: 'Production HORMONES', value: false},
      { department_name: 'Production', value: false},
    ]

  }

  getObserved(){
    this.observed = [
      { observed_in: 'Mfg. Process', value: false},
      { observed_in: 'Facility', value: false},
      { observed_in: 'Materials Quality', value: false},
      { observed_in: 'Vendors', value: false},
      { observed_in: 'Equipment, Utility', value: false},
      { observed_in: 'Materials Quantity', value: false},
      { observed_in: 'Specification, Protocol', value: false},
      { observed_in: 'SOP, Formats', value: false},
      { observed_in: 'Others', value: false}

    ]
  }

  updateObserve(value, i) {
    this.observed[i].status = value;
  }

  getDeviationFor(value) {
    if (value == 'Material') {
      this.isMaterial = true;
      this.isproduct = false;
      this.isEquipment = false;

    } else if (value == 'Product') {
      //  this.getProducts();
      this.isMaterial = false;
      this.isproduct = true;
      this.isEquipment = false;

    } else if (value == 'Equipment') {
      this.isEquipment = true;
      this.isproduct = false;
      this.isMaterial = false;
    } else if (value == 'System') {
      this.isEquipment = false;
      this.isproduct = false;
      this.isMaterial = false;
    } else if (value == 'Area') {
      this.isEquipment = false;
      this.isproduct = false;
      this.isMaterial = false;
    } else if (value == 'Procedure') {
      this.isEquipment = false;
      this.isproduct = false;
      this.isMaterial = false;
    } else if (value == 'Other') {
      this.isEquipment = false;
      this.isproduct = false;
      this.isMaterial = false;
    }
  }

  getMaterial(value) {
    if (value == 'Raw Material') {
      this.israw = true;
      this.ispacking = false;
      this.getMaterialsByTypes(value);
    } else if (value == 'Packing Material') {
      this.israw = false;
      this.ispacking = true;
      this.getMaterialsByTypes(value);
    }
  }

  getMaterialDetails(index) {
    index = index - 1;
    this.selectedMaterial = this.materials[index];
  }


  getProductDetails(index) {
    index = index - 1;
    this.selectedProduct = this.products[index];

  }
  getEquipmentDetails(index) {
    index = index - 1;
    this.selectedEquipment = this.equipments[index];

  }

  getEmployees() {
    this.service.get('employee.php?type=getAllEmployees').subscribe(response => {
      this.employees = response;
    })
  }

  getMaterialsByTypes(value) {
    this.service.get('common.php?type=getMaterialsByTypes&material_type=' + value).subscribe(response => {
      this.materials = response;
    });
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    })
  }

  getEquipments() {
    this.service.get('common.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    })
  }
  
  addMaterials(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['material_code']= this.selectedMaterial['material_code'];
    temp['grade']= this.selectedMaterial['grade'];
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
  }

  delMaterials(index) {
    this.materialList.splice(index, 1);
  }

  addProducts(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['product_code']= this.selectedProduct['product_code'];
    temp['grade']= this.selectedProduct['grade'];
    this.productList[this.productList.length] = temp;
    data.resetForm();
  }

  delProducts(index) {
    this.productList.splice(index, 1);
  }

  addEquipments(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['location']= this.selectedEquipment['location'];
    temp['equipment_code']= this.selectedEquipment['equipment_code'];
    temp['capacity']= this.selectedEquipment['capacity'];
    this.equipmentList[this.equipmentList.length] = temp;
    data.resetForm();
  }

  delEquipments(index) {
    this.equipmentList.splice(index, 1);
  }



  updateDept(value, i) {
    this.departments[i].status = value;
  }

  saveDeviation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }

    let test1 = [];
    for (let i = 0; i < this.observed.length; i++) {
      let conform = this.observed[i];
      if (conform['status']) {
        test1[test1.length] = conform['observed_in'];
      }
    }
    temp['observed_in'] = test1;
    temp['departments'] = test;
    temp['materials'] = this.materialList;
    temp['products'] = this.productList;
    temp['equipments'] = this.equipmentList;
    this.service.post('deviation.php?type=saveDeviations', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/qms/deviation']);
        alert('Record Inserted Successfully');
      } else {
        alert('Failed: An error occured, please try again!');
      }
    })
  }

}