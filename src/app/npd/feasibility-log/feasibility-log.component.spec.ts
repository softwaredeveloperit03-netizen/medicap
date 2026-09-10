import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FeasibilityLogComponent } from './feasibility-log.component';

describe('FeasibilityLogComponent', () => {
  let component: FeasibilityLogComponent;
  let fixture: ComponentFixture<FeasibilityLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FeasibilityLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FeasibilityLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
