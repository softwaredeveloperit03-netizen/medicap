import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RmpmpoComponent } from './rmpmpo.component';

describe('RmpmpoComponent', () => {
  let component: RmpmpoComponent;
  let fixture: ComponentFixture<RmpmpoComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RmpmpoComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RmpmpoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
