import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-appointmentletter',
  templateUrl: './appointmentletter.component.html',
  styleUrls: ['./appointmentletter.component.css']
})
export class AppointmentletterComponent implements OnInit {
  appointmentletter = [];
  employee = [];
  salary_annexure_response;
  salary_response = [];
  offerletter = [];
  candidates = [];
  salary_heads;
  earnings_list = [];
  detuctions_list = [];
  ctc_list = [];
  emp_type='';
  total_earnings = 0;
  total_deductions = 0;
  total_ctc = 0;
  take_home_salary=0;
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

  total_earn_month_amt = 0;
  total_earn_annum_amt = 0;
  total_ded_month_amt = 0;
  total_ded_annum_amt = 0;
  total_ctc_ded_month_amt = 0;
  total_ctc_ded_annum_amt = 0;
  isNew = false;

  isMetro = 'Non Metro';
  isPF = 'Yes';
  isESIC = 'No';

  gross = 0; 
  equipments;

  PF_EMP = 0;
  c_PF = 0;
  PF = 0;
  ESIC = 0;
  c_ESIC = 0;
  bonus = 0;
  p_tax = 200;
  gratuity = 0;
  other = 0;

  contribution_annual = 0;
  ctc_annual = 0;
  departments;
  designations;
  selectedResult=[];
  salary_types

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getgenerated();
    this.getDepartments();
    this.getDesignations();
    this.getSalaryTypes();
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    //this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//

  getEquipments() {
    this.service.get('qaDepartment.php?type=getApprovedEquipments')
      .subscribe(response => {
        this.equipments = response;
      });
  }
  getSalaryTypes() {
    this.service.get('hr/employee.php?type=getSalaryTypes').subscribe(response => {
      this.salary_types = response;
    });
  }
  getDepartments() {
    this.service.get('common.php?type=getNonTechnicalDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  selectEmp(index) {
    this.selectedResult = this.employee[index];
  }
  getDesignations() {
    this.service.get('hr/employee.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }
  norecord = false;
  getgenerated() {
    this.service.get('hr/employee.php?type=generatedappointment').subscribe((response: any) => {
      this.appointmentletter = response;
    });
  }
  openNew() {
    this.isNew = true;
    this.getpendingcandidate();
  }
  getpendingcandidate() {
    this.service.get('hr/employee.php?type=pendingappointment').subscribe((response: any) => {
      this.employee = response;
    });
  }
  // calculateSalary() {
  //   this.basic = parseInt((Math.ceil((this.gross * 60) / 100)).toFixed(2));
  //   this.HRA = parseInt((Math.ceil((this.basic * 20) / 100)).toFixed(2));

  //   let balance = this.gross - (this.basic + this.HRA);

  //   this.Conveyance = parseInt((Math.ceil((balance * 40) / 100)).toFixed(2));
  //   this.specialAllowance = parseInt((balance - this.Conveyance).toFixed(2));

  //   this.PF_EMP = parseInt((Math.ceil((this.basic * 12) / 100)).toFixed(2));
  //   if (this.gross <= 21000) {
  //     this.ESIC = parseInt((Math.ceil((this.gross * 0.75) / 100)).toFixed(2));
  //   } else {
  //     this.ESIC = 0;
  //   }
  //   if (this.gross > 10000) {
  //     this.p_tax = 200;
  //   } else {
  //     this.p_tax = 175;
  //   }
  //   this.deduction = (this.PF_EMP * 1) + (this.ESIC * 1) + (this.p_tax * 1);
  //   this.inhand = this.gross - this.deduction;

  //   if (this.basic >= 15000) {
  //     this.c_PF = parseInt((Math.ceil((15000 * 13.601) / 100)).toFixed(2));
  //   } else {
  //     this.c_PF = parseInt((Math.ceil((this.basic * 13.601) / 100)).toFixed(2));
  //   }
  //   if (this.gross <= 21000) {
  //     this.c_ESIC = parseInt(((this.gross * 3.25) / 100).toFixed(2));
  //   } else {
  //     this.c_ESIC = 0;
  //   }
  //   this.gratuity = parseInt(((this.basic * 4.81) / 100).toFixed(2));
  //   this.bonus = parseInt(((this.gross * 75) / 100).toFixed(2));


  //   this.contribution = parseInt((this.c_PF * 1 + this.c_ESIC + this.medical + this.gratuity).toFixed(2));
  //   this.contribution_annual = parseInt((this.c_PF * 1 + this.c_ESIC + (this.medical * 12) + this.gratuity + this.bonus).toFixed(2));
  //   this.ctc = this.gross * 1 + this.contribution;
  //   this.ctc_annual = (this.gross * 12) + this.contribution_annual;
  // }
  generate(Form) {
    if (Form.valid) {      
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
      temp['emp_id'] = this.selectedResult['id'];
      console.log(JSON.stringify(temp));
  
      this.service.post('hr/candidate.php?type=generateOffterLetter', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          this.isNew = false;
          this.getgenerated();
          alertify.success('Record Generated Successfully !');
        } else {
          alertify.error('Something went to wrong. Try Again!...');
        }
      });
      // temp['type'] = 'employee';
      // temp['basic'] = this.basic;
      // temp['hra'] = this.HRA;
      // temp['conveyance'] = this.Conveyance;
      // temp['medical'] = this.medical;
      // temp['specialallowance'] = this.specialAllowance;
      // temp['PF_EMP'] = this.PF_EMP;
      // temp['c_PF'] = this.c_PF;
      // temp['ESIC'] = this.ESIC;
      // temp['p_tax'] = this.p_tax;
      // temp['gratuity'] = this.gratuity;
      // temp['other'] = this.other;
      // temp['contribution'] = this.contribution;
      // temp['contribution_annual'] = this.contribution_annual;
      // temp['ctc_annual'] = this.ctc_annual;
      // temp['ctc'] = this.ctc;
      // temp['c_ESIC'] = this.ESIC;

      // this.service.post('hr/employee.php?type=generatenewappointment', JSON.stringify(temp)).subscribe(response => {
      //   if (response['status'] == 'success') {
      //     this.isNew = false;
      //     alert('Record Generated Successfully !');
      //     this.getgenerated();
      //   }
      // });
    } else {
      alert('Fill All Feilds');
    }
  }

  print(): void {
    let printContents, popupWin;
    printContents = document.getElementById('print-section').innerHTML;
    popupWin = window.open('', '_blank', 'top=0,left=0,height=100%,width=auto');
    popupWin.document.open();
    popupWin.document.write(`
      <html>
        <head>
          <style>
          //........Customized style.......
          </style>
        </head>
    <body onload="window.print();window.close()">${printContents}</body>
      </html>`
    );
    popupWin.document.close();
  }

  // downloadPdf(id) {
  //   window.open(this.service.url+'pdf/pdfhrDepartment.php?type=downloadappointment&id='+id+'&token=' + localStorage.getItem('token'));
  // }
  downloadPdf(id) {
    this.service.open('hr/employee.php?type=download_appoinment_letter&id=' + id+'&emp_code=' + id);
    //this.service.open('hr/candidate.php?type=generatenewoffer&id='+ id);
  }

  emailOffer(id) {
    this.service.get('pdf/pdfhrDepartment.php?type=emailappointment&id=' + id).subscribe(response => {
      if (response['status'] == "success") {
        alert('Email send successfully');
      } else {
        alert('An error occured');
      }
    });
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

      if (earnings_list[i]['type_flag'] == 'ESIC') {
        if (Number(this.calculateble_gross) <= 21000) {
          var obj = { "Group": "Earnings", "calc_type": earnings_list[i]['calc_type'], "value": earnings_list[i]['calc_type'] == 'Percent' ? earnings_list[i]['percent'] : earnings_list[i]['amount'], "salary_head": earnings_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
          this.earnings_list.push(obj);
        } else {
          obj = { "Group": "Earnings", "calc_type": earnings_list[i]['calc_type'], "value": earnings_list[i]['calc_type'] == 'Percent' ? earnings_list[i]['percent'] : earnings_list[i]['amount'], "salary_head": 'Others', "Amount": amount, "Annum": (amount * 12) }
          this.earnings_list.push(obj);
        }

      }
      else {
        var obj = { "Group": "Earnings", "calc_type": earnings_list[i]['calc_type'], "value": earnings_list[i]['calc_type'] == 'Percent' ? earnings_list[i]['percent'] : earnings_list[i]['amount'], "salary_head": earnings_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
        if (amount > 0) {
          this.earnings_list.push(obj);
        }
      }
      this.total_earn_month_amt = Number(this.total_earn_month_amt) + Number(amount);
      this.total_earn_annum_amt = Number(this.total_earn_annum_amt) + (Number(amount) * 12);
      this.total_earnings = this.total_earn_month_amt;
    }

    //Special Allowance
    obj = { "Group": "Earnings", "calc_type": 'Auto', "value": null, "salary_head": 'Special Allowance', "Amount": 0, "Annum": 0 }
    this.earnings_list.push(obj);



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
      if (ctc_list[i]['type_flag'] == 'ESIC' && (Number(this.calculateble_gross) > 21000)) {
        continue;
      }
      //var amount = parseInt(Number(ctc_list[i]['amount']).toFixed(2));
      if (ctc_list[i]['calc_type'] == 'Percentage') {
        amount = parseInt(((basic_salary * Number(ctc_list[i]['percent'])) / 100).toFixed(2));
      } else {
        amount = parseInt(Number(ctc_list[i]['amount']).toFixed(2));
      }
      this.total_ctc_ded_month_amt = Number(this.total_ctc_ded_month_amt) + Number(amount);
      this.total_ctc_ded_annum_amt = Number(this.total_ctc_ded_annum_amt) + (Number(amount) * 12);
      var obj = { "Group": "CTC Calculations", "calc_type": ctc_list[i]['calc_type'], "value": ctc_list[i]['calc_type'] == 'Percentage' ? ctc_list[i]['percent'] : ctc_list[i]['amount'], "salary_head": ctc_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
      this.ctc_list.push(obj);
      this.total_ctc = Number(this.total_ctc) + Number(ctc_list[i]['amount']);
    }
    //this.take_home_salary = (Number(this.total_earnings) - (Number(this.total_deductions)));
    this.total_ctc = (Number(this.total_earnings) + (Number(this.total_ctc_ded_month_amt)));
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
    // } 
    // this.take_home_salary = ((Number(this.total_earnings)+ Number(this.total_ctc)) - (Number(this.total_deductions)));
  }
}
