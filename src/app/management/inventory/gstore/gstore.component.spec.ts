import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GstoreComponent } from './gstore.component';

describe('GstoreComponent', () => {
  let component: GstoreComponent;
  let fixture: ComponentFixture<GstoreComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GstoreComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(GstoreComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
