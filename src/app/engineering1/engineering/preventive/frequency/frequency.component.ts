import { Component, OnInit } from '@angular/core';
import {DataAccessService} from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-frequency',
  templateUrl: './frequency.component.html',
  styleUrls: ['./frequency.component.css']
})
export class FrequencyComponent implements OnInit {
 

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
    this.service.get('engineering/preventive.php?type=getEquipments').subscribe(response=>{
      this.equipmentslog=response;
      console.log('this.equipmentslog',this.equipmentslog);
    });
  }
 

  view(result) {
    this.selectedResult = result;
    this.resetFrequencySelections();
    this.applySavedFrequencies();
    this.isView = true;
  }

  view1(result) {
    this.selectedResult = result;
    this.isLast = true;
  }

  resetFrequencySelections() {
    this.inspection.forEach((item) => {
      item.checked = false;
      item.last_insp_date = '';
      item.last_prevent_date = '';
    });
    this.preventive.forEach((item) => {
      item.checked = false;
      item.last_insp_date = '';
      item.last_prevent_date = '';
    });
  }

  applySavedFrequencies() {
    const savedInspection = Array.isArray(this.selectedResult['inpection_freequency'])
      ? this.selectedResult['inpection_freequency']
      : [];
    const savedPreventive = Array.isArray(this.selectedResult['prev_maint_frequency'])
      ? this.selectedResult['prev_maint_frequency']
      : [];

    this.inspection.forEach((item) => {
      const saved = savedInspection.find(
        (s) => s && String(s.particular || '').trim() === String(item.particular || '').trim()
      );
      if (saved) {
        item.checked = true;
        item.last_insp_date = saved.last_insp_date || '';
        item.last_prevent_date = saved.last_prevent_date || '';
      }
    });

    this.preventive.forEach((item) => {
      const saved = savedPreventive.find(
        (s) => s && String(s.particular || '').trim() === String(item.particular || '').trim()
      );
      if (saved) {
        item.checked = true;
        item.last_insp_date = saved.last_insp_date || '';
        item.last_prevent_date = saved.last_prevent_date || '';
      }
    });
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
    { id: 1, department_name: 'Engineering' },
    { id: 2, department_name: 'Quality Assurance'},
    { id: 3, department_name: 'Quality Control'},
    { id: 4, department_name: 'Production'},
    { id: 5, department_name: 'R AND D' },
    { id: 6, department_name: 'Store' },
 
  ];


  inspection = [
    { id: 1, particular: 'Daily', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 2, particular: 'Weekly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 3, particular: 'FortNightly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 4, particular: 'Monthly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 5, particular: 'Quarterly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 6, particular: 'Half-Yearly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 7, particular: 'Annually', checked: false, last_insp_date: '', last_prevent_date: '' },
  ];
  preventive = [
    { id: 1, particular: 'Daily', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 2, particular: 'Weekly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 3, particular: 'FortNightly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 4, particular: 'Monthly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 5, particular: 'Quarterly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 6, particular: 'Half-Yearly', checked: false, last_insp_date: '', last_prevent_date: '' },
    { id: 7, particular: 'Annually', checked: false, last_insp_date: '', last_prevent_date: '' },
  ];

  inspection_department = 'Engineering';
  preventive_department = 'Engineering';


  updateEquipmentdata(data) {
    const selectedInspection = this.inspection.filter((item) => item.checked);
    const selectedPreventive = this.preventive.filter((item) => item.checked);

    if (!selectedInspection.length && !selectedPreventive.length) {
      alertify.error('Please select at least one frequency');
      return;
    }

    const obj = {
      id: this.selectedResult['id'],
      equipment_id: this.selectedResult['id'],
      preventive_department: this.preventive_department,
      inspection_department: this.inspection_department,
      inspection: selectedInspection,
      preventive: selectedPreventive,
    };

    this.service.post('engineering/preventive.php?type=update_equipment_frequency', JSON.stringify(obj)).subscribe((response) => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.getEquipmentsLog();
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('Failed:' + response['status']);
      }
    });
  }




  editEquipment() {
    let obj = {
      "id" : this.selectedResult['id'],
      "equipment_id":this.selectedResult['id'],
      "inspection" : this.selectedResult['inpection_freequency'],
      "preventive" : this.selectedResult['prev_maint_frequency'],
    };

 

    this.service.post('engineering/preventive.php?type=update_equipment_schedule', JSON.stringify(obj)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.isLast = false;
        this.getEquipmentsLog();
        this.isbutton = true;
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('Failed:' + result.status);
      }
    });
  }







  mpin ='';
  emp_id = '';
  isDIGI = false;
  isbutton = true;
  status;
  openDigiSign(){
    this.emp_id = localStorage.getItem('emp_id');
    
    this.isDIGI = true;
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
         this.editEquipment();
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }






}
