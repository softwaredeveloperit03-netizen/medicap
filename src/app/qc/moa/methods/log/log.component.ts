import {  AfterViewInit, Component,  } from '@angular/core';
import { ActivatedRoute, Router,Params } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements AfterViewInit {
  constructor(private service: DataAccessService,private route: ActivatedRoute,private router: Router) {}

  plant_id = localStorage.getItem('plant_id');
  details;
  testN: any = [];
  test_master_id;
  spectTestId;
  selectedMethod: any = {};
   isView = false;

   ngAfterViewInit() {

    this.route.paramMap.subscribe((params) => {
      this.getMaterialDetails(params.get('id'));
    });
    this.getMethodDocuments();
    this.getEquipments();
    this.getChemicals();
    this.getGlasswares();
    this.getBalance();
    this.GET_vOLUMENTRIC_sOLUNTIONS();
    
  }

  glasswares;
  getGlasswares() {
    this.service.get('qc/method.php?type=getGlasswares').subscribe((response) => {
        this.glasswares = response;
      });
  }

  balances;
  getBalance() {
    this.service.get('qc/method.php?type=getweighingbalance').subscribe((response) => {
        this.balances = response;
      });
  }

  methodDocuments;
  getMethodDocuments() {
    this.service.get('master/checklist.php?type=getMethodDocuments&doc_type=AssociateDoc').subscribe((response) => {
        this.methodDocuments = response;
      });
  }

  equipements;
  getEquipments() {
    this.service.get('qc/method.php?type=getQCEquipments').subscribe((response) => {
        this.equipements = response;
      });
  }

  chemicals;
  getChemicals() {
    this.service.get('common.php?type=getChemicalsForMoa').subscribe((response) => {
        this.chemicals = response;
      });
  }

  vol_solution;
  GET_vOLUMENTRIC_sOLUNTIONS() {
    this.service.get('qc/method.php?type=GET_vOLUMENTRIC_sOLUNTIONS').subscribe((response) => {
        this.vol_solution = response;
      });
  }


  
   editorConfig = {
    toolbar: [
      ['bold', 'italic', 'underline', 'strike'],  // Basic formatting
      [{ 'header': [1, 2, 3, false] }],           // Header styles
      [{ 'list': 'ordered' }, { 'list': 'bullet' }], // Lists
      [{ 'script': 'sub' }, { 'script': 'super' }], // Subscript/Superscript
      [{ 'indent': '-1' }, { 'indent': '+1' }],   // Indent
      [{ 'direction': 'rtl' }],                   // Text direction
      [{ 'size': ['small', false, 'large', 'huge'] }], // Font sizes
      [{ 'color': [] }, { 'background': [] }],    // Colors
      [{ 'font': [] }],                           // Font family
      [{ 'align': [] }],                          // Text alignment
      ['blockquote', 'code-block'],               // Blockquote, Code block
      // ['link', 'image', 'video'],                 // Insert links, images, videos
      ['clean']                                   // Remove formatting
    ]
  };
 



  

   subtest;
   test_type;
   selectedMethodControl: any = {};
   
  getMaterialDetails(id) {
    this.service.get('qc/method.php?type=get_test_data&id=' + id).subscribe((response) => {
        this.details = response;
        this.testN = this.details[0]['test'];
        this.subtest = this.details['subtest'];
        this.test_type = this.details['test_type'];
        this.test_master_id = this.details[0]['id'];
        this.spectTestId = this.details['spectTestId'];
        
        this.selectedMethod = { ...this.details[0]['methods'][0] }; // For objects
        this.selectedMethodControl = { ...this.details[0]['methods'][0] };
        console.log('details')
        console.log(this.details)
        console.log('selectedMethodControl')
        console.log(this.selectedMethodControl)
        console.log('selectedMethod')
        console.log(this.selectedMethod)
        
        alertify.dialog('alert').set({transition: 'slide',message:'Do Not Paste Content from Word File <br>  Convert the file to pdf then copy content from pdf and paste it',title: '', }).show();
        this.isView = true;
 
        this.get_cromatograms(this.selectedMethod['id']);

        this.instrumentParameterList = this.selectedMethod['hplc'].instrumentParameterList || [];
        this.refractiveIndexList = this.selectedMethod['hplc'].refractiveIndexList || [];
        this.methodParameterList = this.selectedMethod['hplc'].methodParameterList || [];
        this.retentionTimeList = this.selectedMethod['hplc'].retentionTimeList || [];

      });
  }

  
  addGen_ins(data) {
    if (!data.value) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.selectedMethod['Genral_Instruction'].push(temp);  
    data.reset();
  }
  
  del_addGen_ins(index) {
    this.selectedMethod['Genral_Instruction'].splice(index, 1);
  }

  selectedAssociateDoc = [];
  selectedAssocDoc(event,docType) {

    let index = event.target.selectedIndex;
    let value = event.target.value;

    this.selectedAssociateDoc = this.methodDocuments[index - 1];

    if(value == 'ADD NEW'){
      this.addNewDocument(docType);
    }

  }


  addAssociateDoc() {
    let temp = {};
    temp['doc_no'] = this.selectedAssociateDoc['doc_no'];
    temp['doc_name'] = this.selectedAssociateDoc['doc_name'];
    this.selectedMethod['Associative_Document'].push(temp);  
    this.selectedAssociateDoc =[];
  }

  deleteAssocDoc(index) {
    this.selectedMethod['Associative_Document'].splice(index, 1);
  }

  addRefDoc() {
    let temp = {};
    temp['doc_no'] = this.selectedAssociateDoc['doc_no'];
    temp['doc_name'] = this.selectedAssociateDoc['doc_name'];
    this.selectedMethod['Refrenced_Document'].push(temp);  
    this.selectedAssociateDoc =[];
  }

  deleteRefDoc(index) {
    this.selectedMethod['Refrenced_Document'].splice(index, 1);
  }


  term = '';
  defination = '';

 
  addDefination() {
    let temp = {};
    temp['term'] = this.term;
    temp['defination'] = this.defination;
    this.selectedMethod['defination'].push(temp);  
    this.term = '';
    this.defination = '';
  }

  delDefination(index) {
    this.selectedMethod['defination'].splice(index, 1);
  }

  testing_instruction = '';
 
  testinginstruction = [];
  testinginstructiondemo = [];

  addinstruction() {
    let temp = {};
    temp['testing_instruction'] = this.testing_instruction;
     this.testinginstructiondemo.push(temp);
    this.testing_instruction = '';
  }
  deleteinstruction(index) {
    this.testinginstructiondemo.splice(index, 1);
  }


  testing_heading = '';

  addMaininstruction() {

    if (this.testing_heading == '') {
      alertify.error('All fields are required!');
      return;
    }

    let temp = {};
    temp['testing_heading'] = this.testing_heading;
    temp['testing_instruction'] = this.testinginstructiondemo;
    this.selectedMethod['testinginstruction'].push(temp);
    this.testing_heading = '';
    this.testinginstructiondemo = [];


    console.log(this.selectedMethod['testinginstruction']);
  }
  deleteMaininstruction(index) {
    this.selectedMethod['testinginstruction'].splice(index, 1);
  }



  selectedEquipment =[];

  getEquipmentDetails(index) {
    index = index - 1;
    this.selectedEquipment = this.equipements[index];
  }




  equipment_name = '';

  addEquipment() {
    if (this.equipment_name == '') {
      alertify.error('All fields are required!');
      return;
    }

    let temp = {};
    temp['equipment_type'] = this.selectedEquipment['equipment_type'];
    temp['equipment_name'] = this.selectedEquipment['equipment_name'];
    temp['equipment_code'] = this.selectedEquipment['equipment_code'];
    temp['id'] = this.selectedEquipment['id'];
    this.selectedMethod['equipment_instruments'].push(temp);
    this.selectedEquipment = [];
    this.equipment_name = '';
  }


  deleteeq(index) {
    this.selectedMethod['equipment_instruments'].splice(index, 1);
  }

  selectedChemical =[];
  getChemicalDetails(index) {
    index = index - 1;
    this.selectedChemical = this.chemicals[index];
  }

  chemName = '';


  addChemical() {

    if (this.chemName == '') {
      alertify.error('All fields are required!');
      return;
    }

    let temp = {};
    temp['material_name'] = this.selectedChemical['material_name'];
    temp['material_code'] = this.selectedChemical['material_code'];
    temp['grade'] = this.selectedChemical['grade'];
    temp['make'] = this.selectedChemical['make'];
    this.selectedMethod['chemical_reagents'].push(temp);
    this.selectedChemical = [];
    this.chemName = '';
  }


  deleteChem(index) {
    this.selectedMethod['chemical_reagents'].splice(index, 1);
  }



  selectedGlassware = [];

  getGlasswareDetails(index) {
    index = index - 1;
    this.selectedGlassware = this.glasswares[index];
  }
  

  glassware_name = '';
  addGlassware() {
    if (this.glassware_name == '') {
      alertify.error('All fields are required!');
      return;
    }
    let temp = {};
    temp['material_name'] = this.selectedGlassware['material_name'];
    temp['capacity'] = this.selectedGlassware['capacity'];
    temp['class_type'] = this.selectedGlassware['class_type'];
    temp['material_code'] = this.selectedGlassware['material_code'];
    this.selectedMethod['glasswares'].push(temp);
    this.selectedGlassware = [];
    this.glassware_name = ''
  }

  deleteGlass(index){
    this.selectedMethod['glasswares'].splice(index,1);
  }


  selectedBalance = [];
  getBalanceDetails(index) {
    index = index - 1;
    this.selectedBalance = this.balances[index];
  }

  balance_name = '';

  addBalance() {
    if (this.balance_name == '') {
      alertify.error('All fields are required!');
      return;
    }
    let temp = {};
    temp['equipment_name'] = this.selectedBalance['equipment_name'];
    temp['make'] = this.selectedBalance['make'];
    temp['capacity'] = this.selectedBalance['capacity'];
    temp['equipment_code'] = this.selectedBalance['equipment_code'];
    this.selectedMethod['balance'].push(temp);
    this.selectedBalance = [];
    this.balance_name = '';
  }

  deleteBalData(index){
    this.selectedMethod['balance'].splice(index,1);
  }

  selectedSolution = [];
  getSolutionDetails(index) {
    index = index - 1;
    this.selectedSolution = this.vol_solution[index];
  }
  solution_name = '';
  addVolumetric_Solutions(index) {
    if (this.solution_name == '') {
      alertify.error('All fields are required!');
      return;
    }
    let temp = {};
    temp['percentage'] = this.selectedSolution['percentage'];
    temp['unit'] = this.selectedSolution['unit'];
    temp['strength'] = this.selectedSolution['strength'];
    temp['standard_type'] = this.selectedSolution['solution_type'];
    temp['solution_name'] = this.selectedSolution['solution_name'];
    temp['solution_no'] = this.selectedSolution['solution_no'];
    this.selectedMethod['volumetric_solutions'].push(temp);
    this.solution_name = '';
    this.selectedSolution = [];
  }

  delVolumetric_SolutionsList(index){
    this.selectedMethod['volumetric_solutions'].splice(index,1);
  }


  instrumentParameterList =[];
  refractiveIndexList =[];
  retentionTimeList =[];
  methodParameterList =[];

  addInstrumentParameter(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.instrumentParameterList.push(temp);
    data.reset();
  }

  delInstrumentParameter(index){
    this.instrumentParameterList.splice(index,1);
  }



  addRefractiveIndex(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.refractiveIndexList.push(temp);
    data.reset();
  }

  delRefractiveIndex(index){
    this.refractiveIndexList.splice(index,1);
  }

 

  addMethodParameter(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.methodParameterList.push(temp);
    data.reset();
  }

  delMethodParameter(index){
    this.methodParameterList.splice(index,1);
  }



  addRetentionTime(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.retentionTimeList.push(temp);
    data.reset();
  }

  delRetentionTime(index){
    this.retentionTimeList.splice(index,1);
  }








 

  saveMethodData(check) {

    let temp = {};
    temp['spectTestId'] = this.spectTestId;
    temp['test_master_id'] = this.test_master_id;
    temp['check'] = check;

    if(check == 'General Instructions'){
      temp['Genral_Instruction'] = this.selectedMethod['Genral_Instruction'];
    }
    else if(check == 'Purpose'){
      temp['purpose'] = this.selectedMethod['purpose'];
    }
    else if(check == 'Scope'){
      temp['Scope'] = this.selectedMethod['Scope'];
    }
    else if(check == 'Associate Documents'){
      temp['Associative_Document'] = this.selectedMethod['Associative_Document'];
    }
    else if(check == 'Reference Documents'){
      temp['Refrenced_Document'] = this.selectedMethod['Refrenced_Document'];
    }
    else if(check == 'Definition'){
      temp['defination'] = this.selectedMethod['defination'];
    }
    else if(check == 'Safety'){
      temp['Safety'] = this.selectedMethod['Safety'];
    }
    else if(check == 'Testing Instruction'){
      temp['testinginstruction'] = this.selectedMethod['testinginstruction'];
    }
    else if(check == 'Procedure'){
      temp['procedure'] = this.selectedMethod['Procedure'];
    }
    else if(check == 'Equipment Instruments'){
      temp['equipment_instruments'] = this.selectedMethod['equipment_instruments'];
    }
    else if(check == 'Chemical Reagents'){
      temp['chemical_reagents'] = this.selectedMethod['chemical_reagents'];
    }
    else if(check == 'Glasswares'){
      temp['glasswares'] = this.selectedMethod['glasswares'];
    }
    else if(check == 'Weighing Balance'){
      temp['balance'] = this.selectedMethod['balance'];
    }
    else if(check == 'Volumetric Solutions'){
      temp['volumetric_solutions'] = this.selectedMethod['volumetric_solutions'];
    }
    else if(check == 'HPLCData'){
 
      temp['phases'] =  this.selectedMethod['phases'];

      let jadu = {};

      jadu['instrumentParameterList'] = this.instrumentParameterList;
      jadu['refractiveIndexList'] = this.refractiveIndexList;
      jadu['methodParameterList'] = this.methodParameterList;
      jadu['retentionTimeList'] = this.retentionTimeList;
  
      temp['hplc'] = jadu;
    }

     
     this.service.post('qc/method.php?type=saveTestMethodMasterDraft&id='+this.test_master_id,JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success(check+' saved successfully.');
          this.getMaterialDetails(this.test_master_id);
         } else {
          alertify.error('An error Occured, Please try again!');
        }
      });

    }

  

    isNewDoc = 0;

    addNewDocument(docType) {
      if (this.isNewDoc === 0) {
        let type = "Associate";

        if(docType == 'ReferenceDoc'){
          type = "Reference";
        }

        const temp = confirm('Do You Want To Add New '+type+' Document?');
        if (temp) {
          this.isNewDoc = 1;
          const temp1 = prompt(type+' Document Name.');
          this.service.get('master/checklist.php?type=saveMethodDocuments&doc_name=' + temp1+'&docType='+docType).subscribe(response => {
            this.isNewDoc = 1;
            if (response['status'] === 'success') {
              this.getMethodDocuments();
               this.isNewDoc = 0;
              this.selectedAssociateDoc =[];
            }
          });
        }
      }
      this.isNewDoc = 0;
    }


    cromatogramFile: File;

    onFileChangedCromatograms(event) {
      this.cromatogramFile = event.target.files[0];
    }

    addCromatograms(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      const uploadData = new FormData();
      if (this.cromatogramFile !== undefined) {
        uploadData.append('cromatograms',this.cromatogramFile, this.cromatogramFile.name);
      }
      this.service.post('qc/method.php?type=save_cromatograms&test_method_no=' +this.selectedMethod['id'],uploadData).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Saved Successfully...');
            data.reset();
            this.get_cromatograms(this.selectedMethod['id']);
          } else {
            alertify.error('Failed: An error occured, Please try again!');
          }
        });
    }


    cromatograms_list;
    get_cromatograms(test_method_no) {
      this.service.get('qc/method.php?type=get_cromatograms&test_method_no=' + test_method_no).subscribe((response) => {
          this.cromatograms_list = response;
      });
    }


    viewCromatograms(url) {
      url = this.service.url + '../../upload/cromatograms/' + url;
      window.open(url, '_blank');
    }





    delCromatograms(id) {
      this.service.get('qc/method.php?type=delcromatograms&id=' + id).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Successfully Delected!!!!');
            this.get_cromatograms(this.selectedMethod['id']);
          } else {
            alertify.error('Failed: An error occured, Please try again!');
          }
        });
    }


    isPreparation = false;
    preparation ="";
    phases = [];
    solvent_name = '';

    savePreparation(data) {
      if (!data.valid) {
        alertify.error('All fields are required!');
        return;
      }
      let temp = data.value;
      this.preparation = "Accurately Weigh/measure "+temp['accurate_weigh']+" "+
      temp['weight_unit']+" Of "+this.solvent_name+" add to "+temp['add_to']+" ml volumetric flask, markup volume with "+
      temp['markup_with']+" Adjust pH "+temp['adjust_ph']+" with "+temp['adjust_ph_with'];

      data.reset();
      this.isPreparation = false;
    }

    addMobilePhase(data) {
      if (!data.value) {
        alertify.error('All fields are required!');
        return;
      }
      let temp = data.value;
      temp['preparation'] = this.preparation;
      this.selectedMethod['phases'].push(temp);
      data.reset();
    }


    deletphases(index){
      this.selectedMethod['phases'].splice(index , 1);
    }

    addrevisionHistory(data) {
      if (!data.value) {
        alertify.error('All fields are required!');
        return;
      }
      let temp = data.value;
      this.selectedMethod['revision_history'].push(temp);
      data.reset();
    }


    delRevisionHistory(index){
      this.selectedMethod['revision_history'].splice(index , 1);
    }

 

    saveTestMethodMaster() {

      if (this.selectedMethod['revision_history']?.length == 0) {
        alertify.error('Please Add Revision History!!!!!');
        return;
      }

      let temp = {};
      temp['spectTestId'] = this.spectTestId;
      temp['test_master_id'] = this.test_master_id;
      temp['revision_history'] = this.selectedMethod['revision_history'];
    
      this.service.post('qc/method.php?type=saveTestMethodMaster111',JSON.stringify(temp)).subscribe((response) => {
          if (response['status'] === 'success') {
            alertify.success('Method saved successfully.');
            this.router.navigate(['/qc/moa/raw/new']);
          } else {
            alertify.error('An error Occured, Please try again!');
          }
        });
    }























 

  isShown1: boolean = false;  
  toggleShow1() {
    this.isShown1 = !this.isShown1;
  }
  isShown2: boolean = false;  
  toggleShow2() {
    this.isShown2 = !this.isShown2;
  }
  isShown3: boolean = false;  
  toggleShow3() {
    this.isShown3 = !this.isShown3;
  }
  isShown4: boolean = false;  
  toggleShow4() {
    this.isShown4 = !this.isShown4;
  }

  isShown5: boolean = false;  
  toggleShow5() {
    this.isShown5 = !this.isShown5;
  }

  isShown6: boolean = false;  
  toggleShow6() {
    this.isShown6 = !this.isShown6;
  }
  isShown7: boolean = false;  
  toggleShow7() {
    this.isShown7 = !this.isShown7;
  }

  isShown8: boolean = false;  
  toggleShow8() {
    this.isShown8 = !this.isShown8;
  }

  isShown9: boolean = false;  
  toggleShow9() {
    this.isShown9 = !this.isShown9;
  }

  isShown10: boolean = false;  
  toggleShow10() {
    this.isShown10 = !this.isShown10;
  }

  isShown11: boolean = false;  
  toggleShow11() {
    this.isShown11 = !this.isShown11;
  }

  isShown12: boolean = false;  
  toggleShow12() {
    this.isShown12 = !this.isShown12;
  }

  isShown13: boolean = false;  
  toggleShow13() {
    this.isShown13 = !this.isShown13;
  }

  isShown14: boolean = false;  
  toggleShow14() {
    this.isShown14 = !this.isShown14;
  }

  isShown15: boolean = false;  
  toggleShow15() {
    this.isShown15 = !this.isShown15;
  }



 
 
}
