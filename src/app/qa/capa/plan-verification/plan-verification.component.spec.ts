import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PlanVerificationComponent } from './plan-verification.component';

describe('PlanVerificationComponent', () => {
  let component: PlanVerificationComponent;
  let fixture: ComponentFixture<PlanVerificationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PlanVerificationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PlanVerificationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
