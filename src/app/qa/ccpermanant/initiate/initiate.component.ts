  import { Component, OnInit } from '@angular/core';
  import { Router } from '@angular/router';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify; 

@Component({
  selector: 'app-initiate',
  templateUrl: './initiate.component.html',
  styleUrls: ['./initiate.component.css']
})
export class InitiateComponent implements OnInit {
  
    selectedFile:File;
    selectedDev;
    selectedResult=[];
    depts='';
    attributes=[
      {"attribute": "MFC/BMR/LMR/ECR", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Reference SOP", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Specification / Test procedure / Test protocols", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Validation master plan (VMP)", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Risk Assessment", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Process validation / Cleaning validation / Method validation", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Impurity profile (Organic, inorganic and residual impurity, catalyst carryover, elemental, carcinogenic and mutagenic impurities)", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Equipment qualification(DQ, IQ, OQ, PQ)", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Calibration / Preventive maintenance", "status": "", "description": "", "responsibility": ""},
      {"attribute": " Stability study", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Drawing (S), Layouts", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Master list of equipment / Instrument / SOP", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Technical Agreement  ● Customers ● Suppliers  ● External Partners", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Applications to regulatory agencies", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Site master file (SMF/DFM)", "status": "", "description": "", "responsibility": ""},
      {"attribute": "Product Quality", "status": "", "description": "", "responsibility": ""},
      {"attribute": "List of the department and positions required to be informed / trained on the proposed change (attach list if required)", "status": "", "description": "", "responsibility": ""},
    ];
   
    attributess = '';
    requirements=[
      {"requirement": "MFC / LMR / BMR / ECR", "details": "", "department": "", "target_date": ""},
      {"requirement": "Training requirements (attach the list of departments and /or positions)", "details": "", "department": "", "target_date": ""},
      {"requirement": "New or revision of SOP", "details": "", "department": "", "target_date": ""},
      {"requirement": "Specification / Test procedures / Test protocols", "details": "", "department": "", "target_date": ""},
      {"requirement": "Process validation / Cleaning validation / Analytical method validation.", "details": "", "department": "", "target_date": ""},
      {"requirement": "Qualification (DQ/IQ/OQ/PQ/ Requalification)", "details": "", "department": "", "target_date": ""},
      {"requirement": "Calibration / Preventive maintenance", "details": "", "department": "", "target_date": ""},
      {"requirement": "Stability Study", "details": "", "department": "", "target_date": ""},
      {"requirement": "Drawing, Layouts", "details": "", "department": "", "target_date": ""},
      {"requirement": "Master list of equipments / Instrument / SOP", "details": "", "department": "", "target_date": ""},
      {"requirement": "Method validation / Verification", "details": "", "department": "", "target_date": ""},
      {"requirement": "Vendor Qualification", "details": "", "department": "", "target_date": ""},
      {"requirement": "Assessment of the risk in connection to the proposed changes", "details": "", "department": "", "target_date": ""},
    ];
   
    requirementss = '';
    final_completion_date = '';

    selectedIndex = -1;
    constructor(private service:DataAccessService,private router:Router) { }
  
    ngOnInit(): void {
      this.service.observableDepartment.subscribe(response =>{
        this.depts = response;
      });
      this.getCCDetails();
      this.getDepartments();
      this.getClients();
      this.getProducts();
      this.getMaterial();
      this.getEquipments();

    }
    clients;
    results1
    getProducts() {
      this.service.get('master/product.php?type=getBrandProductsLog').subscribe(response => {
        this.results1 = response;
      
      });
    }
    materials;
    getMaterial() {
      this.service.get('master/material.php?type=getMaterials&material_type=Raw Material').subscribe(response => {
        this.materials = response;      
      });
    }
    Equipments;
    getEquipments() {
      this.service.get('master/equipment.php?type=getEquipments').subscribe(response => {
        this.Equipments = response;      
      });
    }
    getClients() {
      this.service.get('common.php?type=getClients').subscribe(response => {
        this.clients = response;
      });
  
      console.log(this.clients);
    }
    departments;
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
    add(data) {
      let temp = data.value;
      let temp1:any = {};
      temp1["attribute"] = temp["attribute"];
      temp1["status"] = temp["status"];
      temp1["description"] = temp["description"];
      temp1["responsibility"] = temp["responsibility"];
      this.attributes[this.attributes.length ]= temp1;
      data.reset();
    }
    add1(data) {
      let temp = data.value;
      let temp1:any = {};
      temp1["requirement"] = temp["requirement"];
      temp1["details"] = temp["details"];
      temp1["department"] = temp["department"];
      temp1["target_date"] = temp["target_date"];
      this.requirements[this.requirements.length ]= temp1;
      data.reset();
    }
    saveData(data){
      if(!data.valid){
        alertify.error('All feilds are required');
        return;
      }
      let temp = data.value;
      temp['actions']=this.attributes;
      temp['requirement']=this.requirements;
      temp['final_target'] = this.final_completion_date;
      this.service.post('qms/ccpermanant.php?type=initiateCC',JSON.stringify(data.value)).subscribe(response=>{
        if(response['status']=='success'){
          alertify.success('data save successfuly');
          this.router.navigate(['/qms/ccpermanant']);
          data.resetForm();
        }else{
          alertify.error('some error Occured!');
        }
      });
  
    }
    onFileChanged(event) {
      this.selectedFile = event.target.files[0];
    }
  
    addParticular(data) {
      if(!data.valid){
        alertify.error("All fields are required");
        return;
      }
      const temp = data.value;
      const uploadData = new FormData();
  
      for (let key in temp) {
        let value = temp[key];
        uploadData.append(key, value);
      }
  
      if (this.selectedFile !== undefined) {
        uploadData.append('attachment', this.selectedFile, this.selectedFile.name);
      }
  
      this.service.post('qms/ccpermanant.php?type=uploadAttachment&cc_no='+this.selectedResult['cc_no'],uploadData).subscribe(response => {
        if (response['status'] == 'success') {
          this.getCCDetails();
          alertify.success('Attachment Uploaded Successfully');
          // this.isView=false;
          // this.getPendingAttachements();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
  
    viewfile(link) {
      window.open(this.service.url + 'upload/ccpermanant/' + link);
    }
  
  
    getCCDetails() {
      this.service.get('qms/ccpermanant.php?type=getCCDetails&cc_no='+this.selectedResult['cc_no']).subscribe((response: any) => {
        this.selectedResult = response;
      });
    }
  
  
  }
  