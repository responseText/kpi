<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * ข้อมูลส่วนตัวของผู้ใช้ที่ล็อกอินอยู่ — แก้ไขข้อมูลส่วนบุคคล + เปลี่ยนรหัสผ่านของตัวเอง
 * เข้าถึงได้ทุกผู้ใช้ (ไม่ต้องมีสิทธิ์เมนู)
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user()->load('employee.prefix', 'employee.position', 'employee.division', 'kpiLevel');

        return view('profile.edit', compact('user'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('profile.edit')->with('success', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        // คอลัมน์ password ถูก cast เป็น 'hashed' อยู่แล้ว — กำหนดค่าดิบได้เลย ระบบจะ hash ให้
        $request->user()->update([
            'password' => $request->validated()['password'],
        ]);

        return redirect()->route('profile.edit')->with('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}
