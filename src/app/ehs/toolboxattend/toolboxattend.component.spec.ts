import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ToolboxattendComponent } from './toolboxattend.component';

describe('ToolboxattendComponent', () => {
  let component: ToolboxattendComponent;
  let fixture: ComponentFixture<ToolboxattendComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ToolboxattendComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ToolboxattendComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
