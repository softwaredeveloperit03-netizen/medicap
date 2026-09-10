import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CifComponent } from './cif.component';

describe('CifComponent', () => {
  let component: CifComponent;
  let fixture: ComponentFixture<CifComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CifComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CifComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
