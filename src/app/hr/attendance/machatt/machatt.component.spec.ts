import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MachattComponent } from './machatt.component';

describe('MachattComponent', () => {
  let component: MachattComponent;
  let fixture: ComponentFixture<MachattComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MachattComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MachattComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
