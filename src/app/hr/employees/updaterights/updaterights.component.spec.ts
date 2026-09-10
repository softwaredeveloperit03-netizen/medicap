import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UpdaterightsComponent } from './updaterights.component';

describe('UpdaterightsComponent', () => {
  let component: UpdaterightsComponent;
  let fixture: ComponentFixture<UpdaterightsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UpdaterightsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UpdaterightsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
