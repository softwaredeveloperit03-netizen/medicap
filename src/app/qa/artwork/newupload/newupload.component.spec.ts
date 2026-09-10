import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewuploadComponent } from './newupload.component';

describe('NewuploadComponent', () => {
  let component: NewuploadComponent;
  let fixture: ComponentFixture<NewuploadComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewuploadComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewuploadComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
