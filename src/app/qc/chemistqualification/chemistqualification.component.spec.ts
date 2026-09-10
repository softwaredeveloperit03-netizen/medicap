import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChemistqualificationComponent } from './chemistqualification.component';

describe('ChemistqualificationComponent', () => {
  let component: ChemistqualificationComponent;
  let fixture: ComponentFixture<ChemistqualificationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ChemistqualificationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChemistqualificationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
