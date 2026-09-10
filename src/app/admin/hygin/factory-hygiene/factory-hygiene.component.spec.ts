import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FactoryHygieneComponent } from './factory-hygiene.component';

describe('FactoryHygieneComponent', () => {
  let component: FactoryHygieneComponent;
  let fixture: ComponentFixture<FactoryHygieneComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FactoryHygieneComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FactoryHygieneComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
