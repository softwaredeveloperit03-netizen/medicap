import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeptCommentComponent } from './dept-comment.component';

describe('DeptCommentComponent', () => {
  let component: DeptCommentComponent;
  let fixture: ComponentFixture<DeptCommentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeptCommentComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeptCommentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
