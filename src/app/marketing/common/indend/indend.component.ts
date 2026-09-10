import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-indend',
  templateUrl: './indend.component.html',
  styleUrls: ['./indend.component.css']
})
export class IndendComponent implements OnInit {
  isShow = false;
  isNewIndend = false;
  material_type = 'Raw Material';
  material_subtype = 'API';
  material_code = '';
  material_name = '';
  grade = 'IP';
  unit = 'Kg';
  required_qty;
  requirement = 'Regular';
  vendor;
  within_days;
  isRawMaterial = true;
  isPackingMaterial = false;
  isChemicalMaterial = false;
  isNewMaterial = false;
  isOther = false;
  materials;
  vendors;
  purpose = 'Production';

  materialList = [];
  index = 0;

  dashboardActive = true;
  managementActive = false;

  indend_materials;
  grades;

  generalIndends;
  general_materials;
  isGeneralMaterial = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getIndends();
    this.getMaterials();
    this.getGeneralIndends();
  }

  getIndends() {
    this.service.get('purchase.php?type=getIndends').subscribe(response => {
      this.indend_materials = response;
    });
  }

  getGeneralIndends() {
    this.service.get('purchase.php?type=getGeneralIndends').subscribe(response => {
      this.generalIndends = response;
    });
  }

  getGeneralMaterials(value) {
    this.service.get('qaDepartment.php?type=getGeneralMaterials&material_type=' + value).subscribe(response => {
      this.general_materials = response;
    });
  }

  newGeneralIndend() {
    this.materialList = [];
    this.index = 0;
    this.isGeneralMaterial = true;
  }

  checkMaterialType(material) {
    if (material === "Raw Material") {
      this.isRawMaterial = true;
      this.isPackingMaterial = false;
      this.isChemicalMaterial = false;
    } else if (material === "Packing Material"){
      this.isRawMaterial = false;
      this.isPackingMaterial = true;
      this.isChemicalMaterial = false;
    } else if(material === "Chemicals") {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isChemicalMaterial = true;
    } else if (material === "Other") {
      this.isOther = true;
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isChemicalMaterial = false;
    } else {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isChemicalMaterial = false;
    }
  }

  getMaterials() {
    this.service.get('purchase.php?type=getIndendMaterials&material_type=' + this.material_type + '&material_subtype=' + this.material_subtype)
    .subscribe(response => {
      this.materials = response;
    });
  }

  getVendors() {
    this.service.get('purchase.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  addMaterial() {
    if (this.material_name == '') {
      alert('Material Name is Required');
      return;
    } else if (this.grade == '') {
      alert('Material Grade is Required');
      return;
    } else if (this.required_qty <= 0) {
      alert('Required Qty is Required');
      return;
    } else if (this.unit == '') {
      alert('Unit is Required');
      return;
    } else if (this.within_days <= 0) {
      alert('Within Days is Required');
      return;
    } else if (this.purpose == '') {
      alert('Purpose is Required');
      return;
    } else if (this.vendor == '') {
      alert('Vendor is Required');
      return;
    } else if (this.requirement == '') {
      alert('Requirement is Required');
      return;
    }
    let tempList = {};
    tempList["material_type"] = this.material_type;
    tempList['material_subtype'] = this.material_subtype;
    tempList['material_code'] = this.material_code;
    tempList['material_name'] = this.material_name;
    tempList['grade'] = this.grade;
    tempList['required_qty'] = this.required_qty;
    tempList['unit'] = this.unit;
    tempList['requirement'] = this.requirement;
    tempList['vendor'] = this.vendor;
    tempList['within_days'] = this.within_days;
    tempList['purpose'] = this.purpose;

    this.grade = 'IP';
    this.required_qty = 0;
    this.requirement = 'Regular';
    this.within_days = 0;
    this.unit = 'Kg';
    this.material_type = 'Raw Material';
    this.material_subtype= 'API';
    this.purpose = 'Production';

    this.materialList[this.index] = tempList;
    this.index++;
    const element1 = document.getElementById('material_type') as HTMLElement;
    element1.focus();
  }

  addGeneralMaterial(data) {
    let index = this.materialList.length;
    this.materialList[index] = data.value;
    data.reset();
  }

  saveIndend() {
    this.service.post('purchase.php?type=submitIndend', JSON.stringify(this.materialList))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.index = 0;
        this.getIndends();
        this.dashboardActive = true;
        this.managementActive = false;
        alert('Indend has been sent for Department Head Approval');
        this.materialList = [];
      } else {
        alert("Error:" + response['status']);
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

  getMaterialGrades(index) {
    index = index - 1;
    this.material_name = this.materials[index].material_name;
    this.service.get('qcDepartment.php?type=getMaterialGrades&material_type=' + this.material_type + '&material_subtype=' + this.material_subtype + '&value=' + this.material_name).subscribe(response => {
      this.grades = response;
    });
  }

  getMaterialCode(index) {
    index = index - 1;
    this.material_code = this.grades[index].material_code;
    this.unit = this.grades[index].unit;
  }
  
  deleteProduct(index) {
    this.materialList.slice(index, 1);
    this.index--;
  }

  printIndend(value) {
    window.open(this.service.url + 'print/indend.php?indend_no=' + value + '&token=' + localStorage.getItem('token'), '_blank');
  }

  saveGeneralIndend() {
    this.service.post('purchase.php?type=saveGeneralIndend', JSON.stringify(this.materialList)).subscribe(response => {
      if (response['status'] == "success") {
        alert("Saved Successfully");
        this.materialList = [];
        this.isGeneralMaterial = false;
        this.getGeneralIndends();
      } else {
        alert('An error occured, Please try again');
      }
    });
  }
}
