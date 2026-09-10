import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RiskassessmentComponent } from './riskassessment.component';

describe('RiskassessmentComponent', () => {
  let component: RiskassessmentComponent;
  let fixture: ComponentFixture<RiskassessmentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RiskassessmentComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RiskassessmentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
