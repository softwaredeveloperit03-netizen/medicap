import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  details;

  test_description = '';


  chemicals;
  selectedDescriptions = [];
  equipements;
  glasswares;
  hplcs;

  options = [
    { "name": "Chemical / Reagents","option": "chemical", "status": false, "list": []},
    { "name": "Equipments / Instruments","option": "equipment", "status": false, "list": []},
    { "name": "Glasswares","option": "glassware", "status": false, "list": []},
    { "name": "Dilutions","option": "dilution", "status": false, "list": []},
    { "name": "Calculations","option": "calculation", "status": false, "list": []},
    { "name": "Standards","option": "standard", "status": false, "list": []},
    { "name": "HPLC Column","option": "hplc", "status": false, "list": []},
    { "name": "Volumetric Solutions","option": "volumetric", "status": false, "list": []},
  ];

  selectedChemical = [];
  selectedEquipment = [];
  selectedGlassware = [];
  selectedHPLC=[];
  formula = '';

  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router) { }

  ngOnInit() {
    this.route.paramMap.subscribe(params => {
      this.getMaterialDetails(params.get('id'));
    });
    this.getChemicals();
    this.getEquipments();
    this.getGlasswares();
    this.getHPLCs();
  }

  getMaterialDetails(id) {
    this.service.get('qc/method.php?type=getMaterialDetails&id=' + id).subscribe(response => {
      this.details = response;
      this.isView = true;
    });
  }

  getChemicals() {
    this.service.get('qc/method.php?type=getChemicals').subscribe(response => {
      this.chemicals = response;
    });
  }

  getEquipments() {
    this.service.get('qc/method.php?type=getEquipments').subscribe(response =>{
      this.equipements = response;
    });
  }

  getGlasswares() {
    this.service.get('qc/method.php?type=getGlasswares').subscribe(response => {
      this.glasswares = response;
    });
  }

  getHPLCs() {
    this.service.get('qc/method.php?type=getHPLCs').subscribe(response => {
      this.hplcs = response;
    });
  }
  getHPLCdetails(index){
    index = index - 1;
    this.selectedHPLC = this.hplcs[index];
  }
  addHPLC(index) {
    let list = this.options[index].list;
    list[list.length] = this.selectedHPLC;
    this.selectedHPLC = [];
  }
  addDescription() {
    if (this.test_description.length > 0) {
      let index = this.selectedDescriptions.length;
      let temp = {};
      temp['test_description'] = this.test_description;
      this.selectedDescriptions[index] = temp;
      this.test_description = '';
    }
  }

  getChemicalDetails(index) {
    index = index - 1;
    this.selectedChemical = this.chemicals[index];
  }

  addChemical(index) {
    let list = this.options[index].list;
    list[list.length] = this.selectedChemical;
    this.selectedChemical = [];
  }

  getEquipmentDetails(index) {
    index = index - 1;
    this.selectedEquipment = this.equipements[index];
  }

  addEquipment(index) {
    let list = this.options[index].list;
    list[list.length] = this.selectedEquipment;
    this.selectedEquipment = [];
  }

  getGlasswareDetails(index) {
    index = index - 1;
    this.selectedGlassware = this.glasswares[index];
  }

  addGlassware(index) {
    let list = this.options[index].list;
    list[list.length] = this.selectedGlassware;
    this.selectedGlassware = [];
  }

  addFormulas(index) {
    let list = this.options[index].list;
    list[list.length] = this.formula;
    this.formula = '';
  }

  saveTestMethodMaster() {
    let temp = this.details;
    let test = [];

    let test1 = {};
    test1["name"] = "Procedure / method Description";
    test1["option"] = "procedure";
    test1["status"] = true;
    test1["list"] = this.selectedDescriptions;
    test[test.length] = test1;

    for (let i = 0; i < this.options.length; i++) {
      let option = this.options[i];
      if (option['status'] == true) {
        if (option['list'].length == 0) {
          alertify.error('All fields are required');
          return;
        }
        test[test.length] = option;
      }
    }

    temp['description'] = test;
    
    this.service.post('qc/method.php?type=saveTestMethodMaster', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Test saved successfully.');
        this.router.navigate(['/moa/']);
      } else {
        alertify.error('An error Occured, Please try again!');
      }
    });
  }

}
