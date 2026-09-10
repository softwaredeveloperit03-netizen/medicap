import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ContractorAgreementComponent } from './contractor-agreement.component';

describe('ContractorAgreementComponent', () => {
  let component: ContractorAgreementComponent;
  let fixture: ComponentFixture<ContractorAgreementComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ContractorAgreementComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ContractorAgreementComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
