import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-chemistqualification',
  templateUrl: './chemistqualification.component.html',
  styleUrls: ['./chemistqualification.component.css']
})
export class ChemistqualificationComponent implements OnInit {
  show_only_annexure = false;
  isAnnexure = false
  isupdateAnnexure = false
  isView = false;
  isPassword = false;
  employees;
  emp_id;
  departments;
  designations;
  emp_name = '';
  department_name = '';
  department = '';
  designation = '';
  status = '';
  family_members = [];
  academics = [];
  employeement = []
  length;
  amtList = [];
  qualiList = [];
  languages = [];
  selectedResult = [];
  documents = [];
  from_date: any;
  isedit: boolean;
  salary_annexure_response;
  salary_response = [];
  offerletter = [];
  candidates = [];
  salary_heads;
  earnings_list = [];
  detuctions_list = [];
  ctc_list = [];
  salary_types;
   
  total_earnings = 0;
  total_deductions = 0;
  total_ctc = 0;

  total_earn_month_amt = 0;
  total_earn_annum_amt = 0;
  total_ded_month_amt = 0;
  total_ded_annum_amt = 0;
  total_ctc_ded_month_amt = 0;
  total_ctc_ded_annum_amt = 0;
  qualifications;
  isMetro = 'Non Metro';
  isPF = 'Yes';
  isESIC = 'No';
  special_allowance = 0;
  gross_salary = 0;
  deduction = 0;
  contribution = 0;
  inhand = 0;
  ctc = 0;
  item = [];
  basic = 0;
  HRA = 0;
  Conveyance = 0;
  medical = 0;
  specialAllowance = 0;
  educationalAllowance = 0;
  candidate_name = '';
  fixed_earnings_total = 0;
  calculateble_gross = 0;
  PF_EMP = 0;
  c_PF = 0;
  PF = 0;
  ESIC = 0;
  c_ESIC = 0;
  bonus = 0;
  p_tax = 0;
  canteen = 0;
  other = 0;
  appointmentletter = [];
  employee = [];
  gratuity = 0;
  emp_type = '';
  take_home_salary = 0;
  contribution_annual = 0;
  ctc_annual = 0;
  plant_id;
  
  constructor(private service: DataAccessService, private router: Router) {

  }

 
  ngOnInit() {

    this.service.observableDepartment.subscribe(response => {
      this.plant_id = this.service.getPlantConfigFields("plant_id")
      this.departments = response;
    });
    this.getSalaryTypes();
    this.getEmployees();
    this.getMahesh();
  }

  getEmployees() {
    // this.service.get('hr/emp.php?type=getemp').subscribe(response => {
    this.service.get('hr/employee.php?type=getEmployeesList&department_name=' + this.department + '&designation=' + this.designation + '&status=' + this.status+'&qc_chemist=YES').subscribe(response => {
      this.employees = response;
    });
  }
  getSalaryTypes() {
    this.service.get('hr/employee.php?type=getSalaryTypes').subscribe(response => {
      this.salary_types = response;
      console.log('crazy',this.salary_types)
    });
  }
  designation_types;
  getMahesh() {
    this.service.get('hr/employee.php?type=getDesignationTypes').subscribe(response => {
      this.designation_types = response;
      console.log('rd',this.designation_types)
    });
  }
  parse(obj) {
    if (obj == null || obj.length == 0 || obj == '[]') {
      return [];
    } else {
      return JSON.parse(obj);
    }
  }
  showAnnexure(index) {
    this.selectedResult = this.employees[index];
    this.getSalaryAnnexure(true);
    this.isView = false;
    this.isAnnexure = false;
    this.isupdateAnnexure = false;
    this.show_only_annexure = true;
  }
  
  view(index) {
    this.selectedResult = this.employees[index];
    if (this.selectedResult['familyList'].length > 0)
      this.family_members = this.selectedResult['familyList'];
   if (this.selectedResult['qualiList'].length > 0)
      this.academics = JSON.parse(this.selectedResult['qualiList']);
  if (this.selectedResult['employeement_list'].length > 0)
      this.employeement = JSON.parse(this.selectedResult['employeement_list']);
    if (this.selectedResult['salary_list'].length > 0)
      this.amtList = JSON.parse(this.selectedResult['salary_list']);
   if (this.selectedResult['qualiList'].length > 0)
      this.qualiList = JSON.parse(this.selectedResult['qualiList']);
   if (this.selectedResult['Languages'].length > 0)
      this.languages = JSON.parse(this.selectedResult['Languages']);
    if (this.selectedResult['document_list1'].length > 0)
      this.documents = this.selectedResult['document_list1'];

    this.isView = true;
    this.getSalaryAnnexure(true);
    this.show_only_annexure = true;
  }

  
  edit(index) {
   // this.router.navigate(['/hr/employees/edit/' + this.employees[index].emp_id]);
   this.router.navigate(
    ['//hr/employees/edit/'],
    { queryParams: { emp_id:  this.employees[index].emp_id } }
  );
    this.isedit = true;
  }
  inactive(index){
    this.selectedResult = this.employees[index];
    // if (!data.valid) {
    //   alertify.error('All fields are required');
    //   return;
    // }
    this.service.post('hr/employee.php?type=inactive_emp&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Updated Successfully');
        this.isPassword = false;
        this.emp_id = '';
        this.getEmployees();
      } else {
        alertify.success('Employee Deleted Successfully');
      }
    });
  }

  del(emp_id) {
    this.emp_id = emp_id;
    this.isPassword = true;
  }

  verify(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('hr/employee.php?type=delEmployee&emp_code=' + this.emp_id, JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Updated Successfully');
        this.isPassword = false;
        this.emp_id = '';
        this.getEmployees();
      } else {
        alertify.error('Invalid Password');
      }
    });
  }
  downloadEmpForm() {
    this.service.open('hr/employee.php?type=downloadEmployeeform&id=' + this.selectedResult['emp_id']);
  }
  downloadSalaryAnnexure() {
    this.service.open('hr/employee.php?type=download_salary_annexure&emp_code=' + this.selectedResult['emp_id']+'&salary='+this.take_home_salary);
  }
  download() {
    this.service.open('hr/employee.php?type=downloadEmployeeList&department_name=' + this.department + '&designation=' + this.designation + '&status=' + this.status);
  }

  AllRecord() {
    this.service.get('hr/employee.php?type=getAllEmployeesList').subscribe((response: any) => {
      this.employees = response;
    });
    this.department = '';
    this.designation = '';
    this.status = '';
  }
  getSalaryAnnexure(only_annexure) {
    this.earnings_list = [];
    this.detuctions_list = [];
    this.ctc_list = [];
    this.service.get('hr/employee.php?type=getEmpSalaryAnnexure&id=' + this.selectedResult['annexure_id']).subscribe((response: any) => {
      this.salary_annexure_response = response;
      this.earnings_list = this.salary_annexure_response[0]['earnings'];
      this.detuctions_list = this.salary_annexure_response[0]['deductions'];
      this.ctc_list = this.salary_annexure_response[0]['ctc'];
      this.total_earn_annum_amt = +this.salary_annexure_response[0]['total_earnings'] * 12;
      this.total_earn_month_amt = this.salary_annexure_response[0]['total_earnings'];
      this.total_ctc_ded_month_amt = this.salary_annexure_response[0]['ctc_deductions'];
      this.total_ded_annum_amt = +this.salary_annexure_response[0]['total_deductions'];
      this.take_home_salary = this.salary_annexure_response[0]['take_home_salary'];
      this.total_ctc = this.salary_annexure_response[0]['total_ctc'];
      this.show_only_annexure = only_annexure;

    });
  }

  /*Salary Annexure Functionality*/

  addAnnexure(i) {
    this.selectedResult = this.employees[i];
    this.emp_name = this.selectedResult['firstname'] + ' ' + this.selectedResult['middlename'] + ' ' + this.selectedResult['lastname'] + '(' + this.selectedResult['emp_id'] + ')';
    this.isAnnexure = true;
    this.isView = false;
  }
  last_per_annum_ctc:number;
  addIncrementAnnexure(i) {
    this.selectedResult = this.employees[i];
    this.emp_name = this.selectedResult['firstname'] + ' ' + this.selectedResult['middlename'] + ' ' + this.selectedResult['lastname'] + '(' + this.selectedResult['emp_id'] + ')';
    this.isupdateAnnexure = true;
    this.last_per_annum_ctc=this.selectedResult['ctc_annual']/12;
    this.isView = false;
    console.log(this.last_per_annum_ctc)
  }
  getSalaryHeads() {
    this.service.get('hr/salaryhead.php?type=getSalaryHead&emp_type=' + this.emp_type).subscribe((response: any) => {
      this.salary_response = response;
      this.calculateSalary();

    });
  }
  calculateSalary() {
    if (this.emp_type == undefined || this.emp_type == '') {
      alertify.error('Please select Emp Type to proceed');
      return;
    }
    if (Number(this.gross_salary) == 0) {
      return;
    }
    this.salary_heads = [];
    this.earnings_list = [];
    this.detuctions_list = [];
    this.ctc_list = [];
    var earnings_list = this.salary_response['Earnings'];
    var detuctions_list = this.salary_response['Deductions'];
    var ctc_list = this.salary_response['CTC'];
    if (earnings_list == undefined || earnings_list.length == 0) {
      alertify.error('Salary Heads not configured for  ' + this.emp_type);
      return;
    }
    this.total_earn_annum_amt = 0;
    this.total_earn_month_amt = 0;
    this.total_ded_month_amt = 0;
    this.total_ded_annum_amt = 0;
    this.total_ctc_ded_annum_amt = 0;
    this.total_ctc_ded_month_amt = 0;
    this.calculateble_gross = this.gross_salary;
    this.fixed_earnings_total = 0;
    this.take_home_salary = 0;
    this.total_ctc = 0;
    this.special_allowance = 0;
    // for (let i = 0; i < earnings_list.length; i++) {
    //   if (earnings_list[i]['calc_type'] != 'Percentage') {
    //     this.fixed_earnings_total = this.fixed_earnings_total + Number(earnings_list[i]['amount']);
    //     this.calculateble_gross = (this.gross_salary - this.fixed_earnings_total)
    //   }
    // }
    // if (this.calculateble_gross == 0) {
    //   this.calculateble_gross = this.gross_salary;
    // }

    let basic_salary = 0;
    for (let i = 0; i < earnings_list.length; i++) {
      if (earnings_list[i]['calc_type'] == 'Percentage') {
        var amount = parseInt(((this.calculateble_gross * Number(earnings_list[i]['percent'])) / 100).toFixed(2));
        if (earnings_list[i]['type_flag'] == 'BASIC') {
          basic_salary = amount;
        } else if (earnings_list[i]['type_flag'] == 'HRA') {
          amount = parseInt(((basic_salary * Number(earnings_list[i]['percent'])) / 100).toFixed(2));
        }
      } else {
        var amount = parseInt(Number(earnings_list[i]['amount']).toFixed(2));
      }
      var obj = { "Group": "Earnings", "calc_type": earnings_list[i]['calc_type'], "value": earnings_list[i]['calc_type'] == 'Percent' ? earnings_list[i]['percent'] : earnings_list[i]['amount'], "salary_head": earnings_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
      if (amount > 0) {
        this.earnings_list.push(obj);

      }
      console.log(obj);
    }

    //Special Allowance
    // obj = { "Group": "Earnings", "calc_type": 'Auto', "value": null, "salary_head": 'Special Allowance', "Amount": 0, "Annum": 0 }
    // this.earnings_list.push(obj);



    for (let i = 0; i < detuctions_list.length; i++) {
      let amount = 0;
      if (detuctions_list[i]['type_flag'] == 'ESIC' && (Number(this.calculateble_gross) > 21000)) {
        continue;
      }
      if (detuctions_list[i]['calc_type'] == 'Percentage') {
        amount = parseInt(((basic_salary * Number(detuctions_list[i]['percent'])) / 100).toFixed(2));
      } else {
        amount = parseInt(Number(detuctions_list[i]['amount']).toFixed(2));
      }

      this.total_ded_month_amt = Number(this.total_ded_month_amt) + Number(amount);
      this.total_ded_annum_amt = Number(this.total_ded_annum_amt) + (Number(amount) * 12);
      var obj = { "Group": "Deductions", "calc_type": detuctions_list[i]['calc_type'], "value": detuctions_list[i]['calc_type'] == 'Percentage' ? detuctions_list[i]['percent'] : detuctions_list[i]['amount'], "salary_head": detuctions_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
      this.detuctions_list.push(obj);
      this.total_deductions = this.total_ded_month_amt;

    }

    for (let i = 0; i < ctc_list.length; i++) {
      let amount = 0;
      if (ctc_list[i]['type_flag'] == 'ESIC') {
        amount = 0;
      }
      else {

        if (ctc_list[i]['calc_type'] == 'Percentage') {
          // amount = parseInt(((this.earnings_list[0]['Amount'] * Number(ctc_list[i]['percent'])) / 100).toFixed(2));
          amount = parseInt(((basic_salary * Number(ctc_list[i]['percent'])) / 100).toFixed(2));
        } else {
          amount = parseInt(Number(ctc_list[i]['amount']).toFixed(2));
        }
      }
      var obj = { "Group": "CTC Calculations", "calc_type": ctc_list[i]['calc_type'], "value": ctc_list[i]['calc_type'] == 'Percentage' ? ctc_list[i]['percent'] : ctc_list[i]['amount'], "salary_head": ctc_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
      this.ctc_list.push(obj);

    }

    this.total_earn_month_amt = 0;
    this.total_ctc_ded_month_amt = 0;
    for (let x = 0; x < this.earnings_list.length; x++) {
      this.total_earn_month_amt = Number(this.total_earn_month_amt) + Number(this.earnings_list[x]['Amount']);
    }
    for (let x = 0; x < this.ctc_list.length; x++) {
      this.total_ctc_ded_month_amt = Number(this.total_ctc_ded_month_amt) + Number(this.ctc_list[x]['Amount']);
    }






    //this.take_home_salary = (Number(this.total_earnings) - (Number(this.total_deductions)));
    this.total_ctc = (Number(this.total_earn_month_amt) + (Number(this.total_ctc_ded_month_amt)));
    this.ctc_annual = this.total_ctc * 12;
    this.special_allowance = (Number(this.gross_salary) - Number(this.total_ctc));
    // if (Number(this.special_allowance) > 0) {
    for (let i = 0; i < this.earnings_list.length; i++) {
      if (this.earnings_list[i]['salary_head'] == 'Special Allowance') {
        this.earnings_list[i]['Amount'] = this.special_allowance;
        this.earnings_list[i]['Annum'] = Number(this.special_allowance) * 12;
        this.total_earn_month_amt = (Number(this.total_earn_month_amt) + Number(this.special_allowance));
        this.total_earn_annum_amt = (Number(this.total_earn_month_amt) * 12);
        this.total_ctc = (Number(this.total_earn_month_amt) + (Number(this.total_ctc_ded_month_amt)));
        this.ctc_annual = this.total_ctc * 12;
      }
    }

    if ((Number(this.calculateble_gross) < 21000)) {


      // Esic Calculation
      for (let i = 0; i < this.ctc_list.length; i++) {
        if (this.ctc_list[i]['salary_head'].includes('ESI')) {
          var esic_total = ((this.total_earn_month_amt * 3.25) / 100).toFixed(2);
          this.ctc_list[i]['Amount'] = esic_total;
          this.ctc_list[i]['Annum'] = esic_total;
        }
      }



      this.total_ctc_ded_month_amt = 0;
      this.total_ctc_ded_annum_amt = 0;
      this.total_ctc = 0;
      for (let i = 0; i < this.ctc_list.length; i++) {
        this.total_ctc_ded_month_amt = Number(this.total_ctc_ded_month_amt) + Number(this.ctc_list[i]['Amount']);
        this.total_ctc_ded_annum_amt = this.total_ctc_ded_month_amt * 12;
        this.total_ctc = Number(this.total_ctc) + Number(this.ctc_list[i]['Amount']);
      }
      this.total_ctc = Number(this.total_earn_month_amt) + Number(this.total_ctc);
      this.ctc_annual = this.total_ctc * 12;
      let diff = this.total_ctc - this.gross_salary;
      this.special_allowance = this.special_allowance - diff;
      this.total_earn_month_amt = 0;
      this.total_earn_annum_amt = 0;
      for (let i = 0; i < this.earnings_list.length; i++) {
        if (this.earnings_list[i]['salary_head'] == 'Special Allowance') {
          this.earnings_list[i]['Amount'] = this.special_allowance;
          this.earnings_list[i]['Annum'] = Number(this.special_allowance) * 12;
        }
        this.total_earn_month_amt = (Number(this.total_earn_month_amt) + Number(this.earnings_list[i]['Amount']));
        this.total_earn_annum_amt = (Number(this.total_earn_month_amt) * 12);
      }
      this.total_ctc = Number(this.total_earn_month_amt) + Number(this.total_ctc_ded_month_amt);
      this.ctc_annual = this.total_ctc * 12;
    }

    //Final ESIC calculation on Gross Total
    for (let i = 0; i < this.ctc_list.length; i++) {
      if (this.ctc_list[i]['salary_head'].includes('ESI')) {
        var esic_total = ((this.total_earn_month_amt * 3.25) / 100).toFixed(2);
        this.ctc_list[i]['Amount'] = esic_total;
        this.ctc_list[i]['Annum'] = esic_total;
      }
    }
    //Final Total Calculations
    this.total_earn_month_amt = 0;
    this.total_ctc_ded_month_amt = 0;
    for (let x = 0; x < this.earnings_list.length; x++) {
      // if (this.earnings_list[x]['salary_head'] != "Special Allowance") {
        this.total_earn_month_amt = Number(this.total_earn_month_amt) + Number(this.earnings_list[x]['Amount']);
      // }
    }
    for (let x = 0; x < this.ctc_list.length; x++) {
      this.total_ctc_ded_month_amt = Number(this.total_ctc_ded_month_amt) + Number(this.ctc_list[x]['Amount']);
    }


    this.total_ctc = (Number(this.total_earn_month_amt) + (Number(this.total_ctc_ded_month_amt)));
    this.ctc_annual = this.total_ctc * 12;
    this.special_allowance = (Number(this.gross_salary) - Number(this.total_ctc));
    // if (Number(this.special_allowance) > 0) {
    for (let i = 0; i < this.earnings_list.length; i++) {
      if (this.earnings_list[i]['salary_head'] == 'Special Allowance') {
        this.earnings_list[i]['Amount'] = this.special_allowance;
        this.earnings_list[i]['Annum'] = Number(this.special_allowance) * 12;
        this.total_earn_month_amt = (Number(this.total_earn_month_amt) + Number(this.special_allowance));
       
        this.total_ctc = (Number(this.total_earn_month_amt) + (Number(this.total_ctc_ded_month_amt)));
        this.ctc_annual = this.total_ctc * 12;
      }
    }


    
    this.total_earn_month_amt = 0;
    for (let i = 0; i < this.earnings_list.length; i++) {

      this.total_earn_month_amt = this.total_earn_month_amt + this.earnings_list[i]['Amount'];

    }

    this.total_earn_annum_amt = (Number(this.total_earn_month_amt) * 12);

  }
  saveSalaryAnnexure(Form) {
    const temp = Form.value;
    if (this.earnings_list == undefined || this.earnings_list.length == 0) {
      alertify.error('Please enter gross salary to prepare Salary Annexure');
      return;
    }
    temp['earnings'] = this.earnings_list;
    temp['deductions'] = this.detuctions_list;
    temp['ctc'] = this.ctc_list;
    temp['type'] = 'Employee';
    temp['total_earnings'] = this.total_earn_month_amt;
    temp['total_deductions'] = this.total_ctc_ded_month_amt;
    temp['ctc_deductions'] = this.total_ded_month_amt;
    temp['total_ctc'] = this.gross_salary;
    temp['ctc_annual'] = this.ctc_annual;
    temp['take_home_salary'] = Number(this.total_earn_month_amt) - Number(this.total_ded_month_amt);
    // temp['emp_id'] = this.selectedResult['emp_id'];
    temp['emp_id'] =  this.selectedResult['emp_id'];
    console.log(JSON.stringify(temp));
    

    this.service.post('hr/candidate.php?type=generateOffterLetter', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.isAnnexure = false;
        alertify.success('Record Generated Successfully !');
        this.getEmployees();
      } else {
        alertify.error('Something went to wrong. Try Again!...');
      }
    });
  }
  applicable_date;
  saveincreaseSalaryAnnexure(increamentForm) {
    const temp = increamentForm.value;
    if (this.earnings_list == undefined || this.earnings_list.length == 0) {
      alertify.error('Please enter gross salary to prepare Salary Annexure');
      return;
    }
    temp['earnings'] = this.earnings_list;
    temp['deductions'] = this.detuctions_list;
    temp['ctc'] = this.ctc_list;
    temp['type'] = 'Employee';
    temp['total_earnings'] = this.total_earn_month_amt;
    temp['total_deductions'] = this.total_ctc_ded_month_amt;
    temp['ctc_deductions'] = this.total_ded_month_amt;
    temp['total_ctc'] = this.gross_salary;
    temp['ctc_annual'] = this.ctc_annual;
    temp['applicable_date'] = this.applicable_date;
    temp['take_home_salary'] = Number(this.total_earn_month_amt) - Number(this.total_ded_month_amt);
    temp['emp_id'] = this.selectedResult['emp_id'];
    console.log(JSON.stringify(temp));
    

    this.service.post('hr/candidate.php?type=generateincrementOffterLetter', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.isAnnexure = false;
        alertify.success('Record Generated Successfully !');
        increamentForm.resetForm();
        this.router.navigate(['/hr/employees/increament']);
        this.getEmployees();
      } else {
        alertify.error('Something went to wrong. Try Again!...');
      }
    });
  }
  viewChallan(url) {
    // url = this.service.url + 'upload/challan/' + url;
    // window.open(url, '_blank');
    // window.open(this.service.url+ this.selectedReport['challan_file']);
    //  window.open(this.service.url+'upload/employee' + this.selectedResult['photo']);
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }
  viewResume(url) {
    // url = this.service.url + 'upload/challan/' + url;
    // window.open(url, '_blank');
    // window.open(this.service.url+ this.selectedReport['challan_file']);
    //  window.open(this.service.url+'upload/employee' + this.selectedResult['photo']);
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }


}
