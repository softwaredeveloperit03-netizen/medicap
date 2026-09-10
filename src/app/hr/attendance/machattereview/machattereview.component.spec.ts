import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MachattereviewComponent } from './machattereview.component';

describe('MachattereviewComponent', () => {
  let component: MachattereviewComponent;
  let fixture: ComponentFixture<MachattereviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MachattereviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MachattereviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
