import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DespensingComponent } from './despensing.component';

describe('DespensingComponent', () => {
  let component: DespensingComponent;
  let fixture: ComponentFixture<DespensingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DespensingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DespensingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
