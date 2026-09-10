import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SafetytrainingComponent } from './safetytraining.component';

describe('SafetytrainingComponent', () => {
  let component: SafetytrainingComponent;
  let fixture: ComponentFixture<SafetytrainingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SafetytrainingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SafetytrainingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
