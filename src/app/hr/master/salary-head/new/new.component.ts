import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  ctc_list = []
  ctc_compulsory = []
  earnings = [];
  earnings_compulsory = []
  salary_types;
  deductions_compulsory = []
  deductions = [];
  bonuses :any [];

  emp_type = '';
  salary_calc_type = 'CTC';
  calc_percent = 100;
  pf = "12.00";
  pf_employer = "0";
  // pf_employer = "13.00";
  esic = "0.75"
  bonus_per = "8.33"
  gratuity_per = "4.81"
  esic_employer = "3.25"
  d_per_annum = 0;
  b_per_annum = 0;
  d_per_month = 0;
  b_per_month = 0;
  prof_tax_amt = "200";
  pf_applicable = 'YES'
  esic_applicable = 'No'
  bonus_applicable = 'No'
  gratuity_applicable = 'No'
  total_earnings_percentage: any;
  percentage_left;
  plant_id;
  is_add = false;
  constructor(private service: DataAccessService, private router: Router) { 

    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }

  ngOnInit(): void {
    this.getSalaryTypes();
    this.calculateAdditionPercentage();

  }

  loadCompulsoryHeads(form) {
    let past_data = [];
    if (this.earnings.length > 0) {
      for (let i = 0; i < this.earnings.length; i++) {
        past_data.push(this.earnings[i]);
      }
    }


    this.earnings_compulsory = [];
    this.earnings_compulsory.push({ "salary_head": "Basic Salary", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['basic'], "type_flag": 'BASIC' })
    this.earnings_compulsory.push({ "salary_head": "HRA", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['hra'], "type_flag": 'HRA' })
    if (this.pf_applicable == 'YES') {
      this.earnings_compulsory.push({ "salary_head": "PF Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['pf_employer'], "type_flag": 'PF' })
    }
    if (this.esic_applicable == 'YES') {
      this.earnings_compulsory.push({ "salary_head": "ESIC Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['esic_employer'], "type_flag": 'ESIC' })
    }
    this.earnings = [];
    this.earnings.push({ "salary_head": "Basic Salary", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['basic'], "required": "Yes", "type_flag": 'BASIC' })
    this.earnings.push({ "salary_head": "HRA", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['hra'], "required": "Yes", "type_flag": 'HRA' })



    this.ctc_list = [];
    this.ctc_compulsory = [];
    if (this.pf_applicable == 'YES') {
      this.ctc_list.push({ "salary_head": "PF Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['pf_employer'], "required": "Yes", "type_flag": 'PF' })
      this.ctc_compulsory.push({ "salary_head": "PF Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['pf_employer'], "required": "Yes", "type_flag": 'PF' })
    }

    if (this.esic_applicable == 'YES') {
      this.ctc_list.push({ "salary_head": "ESIC / Mediclaim Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['esic_employer'], "required": "Yes", "type_flag": 'ESIC' })
      this.ctc_compulsory.push({ "salary_head": "ESIC / Mediclaim Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['esic_employer'], "required": "Yes", "type_flag": 'ESIC' })
    }
    if (this.bonus_applicable == 'YES') {
      this.ctc_list.push({ "salary_head": "Bonus", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['bonus_per'], "required": "Yes", "type_flag": 'BONUS' })
      this.ctc_compulsory.push({ "salary_head": "Bonus", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['bonus_per'], "required": "Yes", "type_flag": 'BONUS' })
    }

    if (this.gratuity_applicable == 'YES') {
      this.ctc_list.push({ "salary_head": "Gratuity", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['gratuity_per'], "required": "Yes", "type_flag": 'GRATUITY' })
      this.ctc_compulsory.push({ "salary_head": "Gratuity", "calc_type": "Percentage", "gross_amt": "0.00", "gross_perc": form.value['gratuity_per'], "required": "Yes", "type_flag": 'GRATUITY' })
    }




    if (past_data.length > 0) {
      for (let i = 0; i < past_data.length; i++) {
        this.earnings.push(this.earnings[i]);
      }
    }

    for (let i = 0; i < this.earnings.length; i++) {
      let percentage = (100 * Number(this.earnings[i]['gross_perc'])) / 100;
      this.earnings[i]['gross_amt'] = percentage;
      this.percentage_left = 100 - (Number(this.total_earnings_percentage));
    }

    this.deductions_compulsory = [];

    this.deductions_compulsory.push({ "deduction_head": "Professional Tax", "calc_type": "Amount", "gross_perc": "0.00", "gross_amt": form.value['prof_tax_amt'], "required": "Yes", "type_flag": 'PT' })

    if (this.pf_applicable == 'YES') {
      this.earnings_compulsory.push({ "deduction_head": "PF Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['pf_employer'], "required": "Yes", "type_flag": 'PF' })
    }
    if (this.esic_applicable == 'YES') {
      this.earnings_compulsory.push({ "deduction_head": "ESIC / Mediclaim Contribution By Employer", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['esic_employer'], "required": "Yes", "type_flag": 'ESIC' })
    }
    this.deductions = [];
    this.deductions.push({ "deduction_head": "Professional Tax", "calc_type": "Amount", "gross_perc": "0.00", "gross_amt": form.value['prof_tax_amt'], "required": "Yes", "type_flag": 'PT' })
    if (this.pf_applicable == 'YES') {
      this.deductions.push({ "deduction_head": "PF Contribution By Employee", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['pf'], "required": "Yes", "type_flag": 'PF' })
    }
    if (this.esic_applicable == 'YES') {
      this.deductions.push({ "deduction_head": "ESIC / Mediclaim Contribution By Employee", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": form.value['esic'], "required": "Yes", "type_flag": 'ESIC' })
    }


  }
  getSalaryTypes() {
    this.service.get('hr/employee.php?type=getSalaryTypes').subscribe(response => {
      this.salary_types = response;
    });
  }
  addMnadatoryEarning(form) {
    if (!form.valid) {
      alertify.error('All feilds are Required');
      return;
    }
    if (Number(form.value['hra']) > 100) {
      alertify.error('HRA Precent should not exceed 100%');
      return;
    }
    if (Number(form.value['basic']) > 100) {
      alertify.error('Basic Precent should not exceed 100%');
      return;
    }
    let amount = Number(form.value['hra']) + Number(form.value['pf']) + Number(form.value['basic']) + Number(form.value['esic']);
    if (this.percentage_left == 0) {
      alertify.error('Total Earnings Percentage Achieved');
      return;
    }

    this.loadCompulsoryHeads(form)
    this.calculateAdditionPercentage();
    this.addOtherEarnings();
  }
  addOtherEarnings() {
    let found = false;
    let position = 0;
    if (this.percentage_left > 0) {
      for (let i = 0; i < this.earnings.length; i++) {

        if (this.earnings[i]['type_flag'] == 'Others') {
          position = i;
          this.earnings[i]['gross_perc'] = this.percentage_left
          found = true;
        }
      }
      // if (!found) {
      //   let obj = { "salary_head": "Special Allowances", "gross_amt": "0.00", "calc_type": "Percentage", "gross_perc": this.percentage_left, "required": "Yes", "type_flag": 'Others' };
      //   this.earnings.push(obj);
      // }
    } else {
      for (let i = 0; i < this.earnings.length; i++) {
        if (this.earnings[i]['type_flag'] == 'Others') {
          position = i;
          this.earnings.splice(position, 1);
        }
      }
    }
  }
  addEarning(form) {
    // if (this.percentage_left.length == 0 && form.value['calc_type'] == 'Percentage') {
    //   alertify.error('Earnings Limit 100% Reached!');
    //   return;
    // }
    if (this.earnings_compulsory.length == 0) {
      alertify.error('Please Enter  Basic, HRA values first!');
      return;
    }
    if (!form.valid) {
      alertify.error('All feilds are Required');
      return;
    }


    form['required'] = 'No';
    let amount = 0;

    if (form.value['calc_type'] == 'Percentage') {
      amount = Number(form.value['gross_perc']);
      form.value['gross_amt'] = ((100 * Number(form.value['gross_perc'])) / 100);
    } else {
      amount = Number(form.value['gross_perc']);
      let per = (amount / 100);
      form.value['gross_amt'] = amount;
      form.value['gross_perc'] = 0;//per.toFixed(2);
      amount = per;
    }

    for (let i = 0; i < this.earnings.length; i++) {
      if (this.earnings[i]['salary_head'].trim() == form.value['salary_head'].trim()) {
        alertify.error('Duplicate Salary Heads not allowed');
        return;
      }
    }
    this.earnings.push(form.value);

    this.calculateAdditionPercentage();
    this.addOtherEarnings();
    form.resetForm();
  }

  addDeduction(form) {

    if (this.earnings_compulsory.length == 0) {
      alertify.error('Please Enter  Basic, HRA values first!');
      return;
    }
    if (!form.valid) {
      alertify.error('All feilds are Required');
      return;
    }


    form['required'] = 'No';
    let amount = 0;

    if (form.value['calc_type'] == 'Percentage') {
      amount = Number(form.value['gross_perc']);
      form.value['gross_amt'] = ((100 * Number(form.value['gross_perc'])) / 100);
    } else {
      amount = Number(form.value['gross_perc']);
      let per = (amount / 100);
      form.value['gross_amt'] = amount;
      form.value['gross_perc'] = 0;//per.toFixed(2);
      amount = per;
    }

    for (let i = 0; i < this.deductions.length; i++) {
      if (this.deductions[i]['deduction_head'].trim() == form.value['deduction_head'].trim()) {
        alertify.error('Duplicate Heads not allowed');
        return;
      }
    }
    this.deductions.push(form.value);
    form.resetForm();
    // if (!form.valid) {
    //   alertify.error('All feilds are Required');
    //   return;
    // }
    // this.deductions.push(form.value);
    // form.resetForm();
  }
  addCtc(form) {

    if (this.earnings_compulsory.length == 0) {
      alertify.error('Please Enter  Basic, HRA values first!');
      return;
    }
    if (!form.valid) {
      alertify.error('All feilds are Required');
      return;
    }


    form['required'] = 'No';
    let amount = 0;

    if (form.value['calc_type'] == 'Percentage') {
      amount = Number(form.value['gross_perc']);
      form.value['gross_amt'] = ((100 * Number(form.value['gross_perc'])) / 100);
    } else {
      amount = Number(form.value['gross_perc']);
      let per = (amount / 100);
      form.value['gross_amt'] = amount;
      form.value['gross_perc'] = 0;//per.toFixed(2);
      amount = per;
    }

    for (let i = 0; i < this.ctc_list.length; i++) {
      if (this.ctc_list[i]['salary_head'].trim() == form.value['salary_head'].trim()) {
        alertify.error('Duplicate Heads not allowed');
        return;
      }
    }
    this.ctc_list.push(form.value);
    form.resetForm();
  }
  deleteEarning(i) {
    if (i == -0) {
      this.earnings.splice(0, 7);
      this.earnings_compulsory = [];
    } else {
      this.earnings.splice(i, 1);
    }
    this.calculateAdditionPercentage();
    this.addOtherEarnings();
  }
  deleteDeduction(i) {
    this.deductions.splice(i, 1);

  }


  deleteCtc(i) {
    if (i == -0) {
      this.ctc_list.splice(0, 7);
      this.ctc_compulsory = [];
    } else {
      this.earnings.splice(i, 1);
    }

  }


  save(shiftForm) {
    // if (this.percentage_left != 0) {
    //   alertify.error('Make the Earning calculation to achieve 100%');
    //   return;
    // }
    if (!shiftForm.valid) {
      alertify.error('All feilds are Required');
      return;
    }

    if (this.earnings.length == 0) {
      alertify.error('Please Enter Earnings');
      return;
    }

    if (this.deductions.length == 0) {
      alertify.error('Please Enter Deductions');

    }

    let data = {
      "emp_type": shiftForm.value.emp_type,
      "pf_applicable": shiftForm.value.pf_applicable,
      "salary_type": shiftForm.value.salary_type,
      "salary_calculate_on": shiftForm.value.salary_calc_type,
      "deduction": this.deductions,
      "earning": this.earnings,
      "ctc": this.ctc_list,
    }
    console.log(JSON.stringify(data));

    data.emp_type = this.emp_type;
    //data.bonus = this.bonuses;
    data.deduction = this.deductions;
    data.earning = this.earnings;
    this.service.post('hr/salaryhead.php?type=saveSalaryHead', JSON.stringify(data)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        shiftForm.resetForm();
        this.router.navigate(['/master/salary-head']);
      } else {
        alertify.error(response['status']);
      }

    });
  }

  calcDeduction(value) {
    this.d_per_annum = Number(value) * 12;
  }

  calcBonus(value) {
    this.b_per_annum = Number(value) * 12;
  }

  keyPressNumbers(event, str) {
    var charCode = (event.which) ? event.which : event.keyCode;
    console.log(charCode);
    if (charCode == 46) {
      if (str.indexOf(".") !== -1) {
        return;
      }
    } else {

      if ((charCode < 48 || charCode > 57)) {
        event.preventDefault();
        return false;
      } else {
        return true;
      }
    }
  }
  calculateAdditionPercentage() {
    this.total_earnings_percentage = 0;
    let percentage = 0;
    for (let i = 0; i < this.earnings.length; i++) {
      if (this.earnings[i]['type_flag'] != 'Others') {
        this.total_earnings_percentage = Number(this.total_earnings_percentage) + Number(this.earnings[i]['gross_perc']);
        this.percentage_left = 100 - (Number(this.total_earnings_percentage));
      }
      // if (this.earnings[i]['gross_perc'] == 'Percentage') {
      //   percentage = (100 * Number(this.earnings[i]['gross_perc'])) / 100;
      //   this.total_earnings_percentage = Number(this.total_earnings_percentage) + Number(percentage);
      //   this.percentage_left = 100 - (Number(this.total_earnings_percentage));

      //   //gross_amt
      // } else {
      //   this.total_earnings_percentage = Number(this.total_earnings_percentage) + Number(this.earnings[i]['gross_perc']);
      //   this.percentage_left = 100 - (Number(this.total_earnings_percentage));

      // }
    }


  }
  getSalaryType(data) {
    if (data == 'Add New') {
      this.is_add = true;
    }
  }
  saveSalaryType(data) {
    if (!data.valid) {
      alertify.error('All Fields are Mandatory');
      return;
    }
    let obj = {
      "payroll_type": data.value['payroll_type']
    }
    this.service.post('hr/employee.php?type=save_salary_types', JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Records saved successfully');
        this.getSalaryTypes();
        data.reset();

      } else {
        alertify.error(response['status']);
      }
    });
  }

}
